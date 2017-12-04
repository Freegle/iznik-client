const createTestCafe = require('testcafe')
const { join } = require('path')
const glob = require('glob-promise');

const isCI = process.argv.includes('--ci')
const browsers = isCI ? ['chrome:headless'] : ['chromium'];

(async () => {
  const testcafe = await createTestCafe('localhost')
  const runner = await testcafe.createRunner()
  const failedCount = await runner
    .src(await glob(join(__dirname, '**/*.e2e.js')))
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

