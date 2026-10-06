const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');
(async () => {
  fs.mkdirSync('artifacts/screenshots',{recursive:true});
  const browser=await chromium.launch({channel:process.env.BROWSER_CHANNEL || 'chrome',headless:true});
  const page=await browser.newPage({viewport:{width:1440,height:1000}});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  const base=process.env.TEST_BASE_URL || 'http://127.0.0.1:8080/';
  const checks=[];
  const shot=async name=>page.screenshot({path:`artifacts/screenshots/${name}.png`,fullPage:true});
  const checkWidth=async name=>{assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`${name} horizontal overflow`);checks.push(name+' fits viewport');};
  await page.goto(base);await shot('home-desktop');await checkWidth('desktop home');
  await page.getByRole('link',{name:'Find your favourite'}).click();
  assert.equal(await page.locator('.menu-card').count(),12);checks.push('12 menu cards visible');await shot('menu-desktop');
  await page.getByRole('link',{name:'Kitchen',exact:true}).click();assert.equal(await page.locator('.menu-card').count(),3);checks.push('category navigation');
  await page.getByRole('link',{name:'All the good stuff',exact:true}).click();
  const card=page.locator('.menu-card').first();await card.locator('summary').click();await card.getByLabel('Who is it for?').fill('Mandip');await card.getByLabel('Preparation note').fill('No sugar');await card.getByRole('button',{name:'Add to bag'}).click();
  await page.getByRole('link',{name:'Bag 1'}).click();assert.ok(await page.getByText('For Mandip').isVisible());checks.push('interactive group ordering');await shot('cart-desktop');
  await page.getByRole('link',{name:'Sign in to checkout'}).click();await page.getByRole('link',{name:'Create an account',exact:true}).click();
  await page.getByLabel('Your name').fill('Browser Demo');await page.getByLabel('Email address').fill(`browser-${Date.now()}@example.test`);await page.getByLabel('Password',{exact:true}).fill('BrowserDemo-Password!');await page.getByRole('checkbox').check();await page.getByRole('button',{name:'Create account'}).click();
  await page.getByRole('link',{name:'Bag 1'}).click();await page.getByRole('link',{name:'Continue to checkout'}).click();
  await page.getByLabel('Phone number').fill('0412345678');await page.getByLabel('Collection time').selectOption({index:1});await page.getByLabel('Group or team name').fill('Morning crew');await page.getByRole('checkbox').check();await shot('checkout-desktop');
  await page.getByRole('button',{name:'Place demo order'}).click();assert.ok(await page.getByRole('heading',{name:'A good choice. A great day.'}).isVisible());checks.push('browser checkout and confirmation');await shot('confirmation-desktop');
  for (const width of [390,320]) {
    await page.setViewportSize({width,height:844});
    for(const route of ['home','menu','orders','cart','login','register','privacy']){await page.goto(`${base}index.php?page=${route}`);await checkWidth(`${width}px ${route}`);if(width===390 && ['home','menu','orders'].includes(route))await shot(`${route}-mobile`);}
  }
  await page.setViewportSize({width:1440,height:1000});await page.goto(`${base}index.php?page=login`);
  await page.getByLabel('Email address').fill(process.env.TEST_STAFF_EMAIL||'staff@example.test');await page.getByLabel('Password',{exact:true}).fill(process.env.TEST_STAFF_PASSWORD);await page.getByRole('button',{name:'Sign in',exact:true}).click();
  assert.ok(await page.getByRole('heading',{name:'Good service starts here.'}).isVisible());checks.push('staff browser login');await shot('staff-desktop');
  await page.setViewportSize({width:390,height:844});await checkWidth('mobile staff');await shot('staff-mobile');
  await page.getByRole('link',{name:'Manage menu'}).click();await checkWidth('mobile menu management');
  assert.deepEqual(errors,[]);checks.push('no JavaScript errors');
  fs.writeFileSync(`artifacts/browser-results-${process.env.BROWSER_CHANNEL||'chrome'}.json`,JSON.stringify({executedAt:new Date().toISOString(),browser:process.env.BROWSER_CHANNEL||'chrome',checks},null,2));
  console.log(checks.join('\n'));console.log(`${checks.length} browser checks passed.`);await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
