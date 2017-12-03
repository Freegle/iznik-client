import { Selector } from 'testcafe';

fixture('find stuff')
  .page('http://localhost:3000');
  
test('find a table in Bath', async t => {
    await t
      .click(Selector('.btn').withText('Find stuff'))
      .typeText('.js-postcode', 'BA1 6RD')
      .pressKey('enter')
      .click(Selector('.btn').withText('Next >>'))
      .typeText('.js-search', 'table')
      .pressKey('enter')
      .expect(Selector('.js-subject').textContent).contains('OFFER');
});
