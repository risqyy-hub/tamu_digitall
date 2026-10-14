export default async function run(page) {
  const base = 'http://localhost/buku_tamu_digital'
  const errors = []
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()) })
  page.on('pageerror', (e) => errors.push(String(e)))

  const login = async (u, p) => {
    await page.evaluate(([u, p]) => {
      document.querySelector('input[name=username]').value = u
      document.querySelector('input[name=password]').value = p
      document.querySelector('form').submit()
    }, [u, p])
    await page.waitForURL(/dashboard\.php/, { timeout: 20000 })
    await page.waitForTimeout(1200)
  }
  const nav = async (u) => {
    await page.goto(u, { waitUntil: 'domcontentloaded', timeout: 30000 })
    await page.waitForTimeout(500)
  }

  await nav(base + '/login.php')
  await page.screenshot({ path: 'qa-login.png' })

  await login('admin', 'admin12345')
  const dashCards = await page.locator('.card').count()
  await page.screenshot({ path: 'qa-dashboard.png', fullPage: true })

  await nav(base + '/admin/kunjungan/tambah.php')
  const kunjText = await page.locator('body').innerText()
  await page.screenshot({ path: 'qa-kunjungan-tambah.png', fullPage: true })

  await nav(base + '/admin/laporan/index.php')
  const laporanText = await page.locator('body').innerText()
  const hasPrint = await page.evaluate(() => document.body.innerHTML.includes('window.print()'))
  await page.screenshot({ path: 'qa-laporan.png', fullPage: true })

  const p = await page.context().newPage()
  await p.goto(base + '/login.php', { waitUntil: 'domcontentloaded' })
  await p.evaluate(() => {
    document.querySelector('input[name=username]').value = 'petugas'
    document.querySelector('input[name=password]').value = 'petugas12345'
    document.querySelector('form').submit()
  })
  await p.waitForURL(/dashboard\.php/, { timeout: 20000 })
  await p.goto(base + '/petugas/qr/scanner.php', { waitUntil: 'domcontentloaded' })
  await p.waitForTimeout(700)
  const scanText = await p.locator('body').innerText()
  await p.screenshot({ path: 'qa-scanner.png', fullPage: true })
  await p.close()

  return {
    dashCards,
    kunjunganHasTokenInput: kunjText.includes('Token QR'),
    kunjunganHasManual: kunjText.includes('Manual'),
    laporanHasPrintBtn: hasPrint,
    laporanHasDicetak: laporanText.includes('Dicetak:'),
    scannerHasPindai: scanText.includes('Pindai QR tamu'),
    errors,
  }
}
