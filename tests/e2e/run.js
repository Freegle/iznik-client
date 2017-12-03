const createTestCafe = require('testcafe')
const { join } = require('path')

const isCI = process.argv.includes('--ci')
const browsers = isCI ? ['chromium:headless'] : ['chromium'];

(async () => {
  const testcafe = await createTestCafe('localhost')
  const runner = await testcafe.createRunner()
  const failedCount = await runner
    // should pass in test files via args or use some autodiscovery
    .src(join(__dirname, 'findStuff.js'))
    .browsers(browsers)
    .screenshots('e2e-screenshots')
    .run({
      debugOnFail: !isCI,
    })

  console.log('Tests failed: ' + failedCount)
  testcafe.close()
  if (failedCount > 0) {
    process.exit(1)
  }
})()

