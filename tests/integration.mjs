import assert from 'node:assert/strict';
import { writeFileSync, mkdirSync } from 'node:fs';
const base = process.env.TEST_BASE_URL || 'http://127.0.0.1:8080/';
const staffEmail = process.env.TEST_STAFF_EMAIL || 'staff@example.test';
const staffPassword = process.env.TEST_STAFF_PASSWORD;
if (!staffPassword) throw new Error('Set TEST_STAFF_PASSWORD for a staff account in a disposable test database.');
const results=[];
function check(name, fn) { fn(); results.push({name,status:'passed'}); console.log('PASS:',name); }
const decode = s => s.replaceAll('&amp;','&').replaceAll('&#039;',"'").replaceAll('&quot;','"');
function field(html,name) { const m=html.match(new RegExp(`name="${name}" value="([^"]*)"`)); assert.ok(m,`Missing ${name}`); return decode(m[1]); }
class Client {
  cookie=''; csrf='';
  async request(page='home', data, params={}) {
    const u=new URL('index.php',base); u.search=new URLSearchParams({page,...params});
    const res=await fetch(u,{method:data?'POST':'GET',redirect:'manual',headers:{...(this.cookie?{Cookie:this.cookie}:{}),...(data?{'Content-Type':'application/x-www-form-urlencoded'}:{})},body:data?new URLSearchParams(data):undefined});
    const cookie=res.headers.getSetCookie(); if(cookie.length) this.cookie=cookie.map(v=>v.split(';')[0]).join('; ');
    const html=await res.text(); const csrf=html.match(/name="csrf" value="([^"]+)"/); if(csrf)this.csrf=csrf[1];
    return {status:res.status,html,location:res.headers.get('location'),headers:res.headers};
  }
  async post(page,data) { return this.request(page,{csrf:this.csrf,...data}); }
}
const guest=new Client();
let r=await guest.request();
check('Home and security headers',()=>{assert.equal(r.status,200);assert.match(r.html,/Your daily pause/);assert.match(r.headers.get('content-security-policy'),/frame-ancestors 'none'/)});
r=await guest.request('staff');check('Anonymous staff access redirects to sign-in',()=>assert.equal(r.status,303));
r=await guest.request('does-not-exist');check('Unknown page returns 404',()=>assert.equal(r.status,404));
r=await guest.request('menu');check('Seeded menu is served from database',()=>assert.match(r.html,/Almond croissant/));
r=await guest.request('menu',undefined,{category:'Kitchen'});check('Category filtering',()=>{assert.match(r.html,/Avocado toast/);assert.doesNotMatch(r.html,/name="item_id" value="1"/)});
r=await guest.request('menu',undefined,{q:'zznotamatch'});check('Empty search state',()=>assert.match(r.html,/No matches this time/));
r=await guest.post('menu',{action:'add_cart',csrf:'invalid',item_id:'1',quantity:'1'});check('CSRF forgery rejected',()=>assert.equal(r.status,419));
await guest.request('menu');
r=await guest.post('menu',{action:'add_cart',item_id:'1',quantity:'0'});check('Zero quantity rejected',()=>assert.equal(r.status,422));
await guest.request('menu');
r=await guest.post('menu',{action:'add_cart',item_id:'999999',quantity:'1'});check('Unknown product rejected',()=>assert.equal(r.status,422));
await guest.request('menu');
r=await guest.post('menu',{action:'add_cart',item_id:'1',quantity:'2',recipient:'Mandip',instructions:'<script>alert(1)</script>'});check('Group item added',()=>assert.equal(r.status,303));
r=await guest.request('cart');check('Cart totals and escaped preparation notes',()=>{assert.match(r.html,/\$9\.60/);assert.match(r.html,/For Mandip/);assert.match(r.html,/&lt;script&gt;/);assert.doesNotMatch(r.html,/<script>alert/)});
const lineKey=field(r.html,'key');
r=await guest.post('cart',{action:'update_cart',key:lineKey,quantity:'3'});check('Quantity updated',()=>assert.equal(r.status,303));
r=await guest.request('cart');check('Updated cart total',()=>assert.match(r.html,/\$14\.40/));
await guest.post('cart',{action:'remove_cart',key:lineKey});r=await guest.request('cart');check('Cart removal and empty state',()=>assert.match(r.html,/Your bag is empty/));
await guest.request('register');
r=await guest.post('register',{action:'register',name:'Test',email:'bad-email',password:'SecureTest-Password!',privacy:'yes'});check('Invalid email rejected',()=>assert.equal(r.status,422));
await guest.request('register');
r=await guest.post('register',{action:'register',name:'Test',email:'a@example.test',password:'short',privacy:'yes'});check('Weak password rejected',()=>assert.equal(r.status,422));
await guest.request('register');
const customerEmail=`customer-${Date.now()}@example.test`;
r=await guest.post('register',{action:'register',name:'Jasson Test',email:customerEmail,password:'SecureTest-Password!',privacy:'yes',role:'staff'});check('Customer registered',()=>assert.equal(r.status,303));
r=await guest.request('staff');check('Customer cannot access staff or choose a staff role',()=>assert.equal(r.status,403));
await guest.request('menu');
r=await guest.post('menu',{action:'menu_save',item_id:'1',name:'Hacked',price:'0.01'});check('Customer cannot mutate staff menu',()=>assert.equal(r.status,403));
await guest.request('menu');
await guest.post('menu',{action:'add_cart',item_id:'1',quantity:'2',recipient:'Rudesh',instructions:'No sugar',price:'0.01'});
r=await guest.request('checkout');check('Checkout requires no card details',()=>{assert.equal(r.status,200);assert.doesNotMatch(r.html,/name="(?:card|cvv|card_number)"/)});
const key=field(r.html,'checkout_key');const total=field(r.html,'expected_total');const slot=decode(r.html.match(/<option value="(\d{4}-[^"]+)"/)[1]);
const payload={action:'checkout',checkout_key:key,expected_total:total,customer_name:'Jasson Test',phone:'0412345678',pickup_at:slot,group_name:'Team breakfast',notes:'Please label the cups.',payment_result:'approved',payment_consent:'yes'};
r=await guest.post('checkout',{...payload,phone:'123'});check('Invalid phone rejected',()=>assert.equal(r.status,422));
await guest.request('checkout');r=await guest.post('checkout',{...payload,pickup_at:'2020-01-01 08:00:00'});check('Past collection rejected',()=>assert.equal(r.status,422));
await guest.request('checkout');r=await guest.post('checkout',{...payload,payment_consent:''});check('Missing simulation acknowledgment rejected',()=>assert.equal(r.status,422));
await guest.request('checkout');r=await guest.post('checkout',{...payload,payment_result:'declined'});check('Declined simulated payment rejected',()=>assert.equal(r.status,422));
r=await guest.request('orders');check('Declined payment creates no order',()=>assert.match(r.html,/Your first good choice/));
await guest.request('checkout');r=await guest.post('checkout',{...payload,expected_total:'1'});check('Altered total rejected',()=>assert.equal(r.status,422));
await guest.request('checkout');r=await guest.post('checkout',payload);check('Approved checkout persists an order',()=>assert.equal(r.status,303));
const reference=new URL(r.location,base).searchParams.get('ref');
await guest.request('orders');r=await guest.post('checkout',payload);check('Checkout replay returns the original order',()=>{assert.equal(r.status,303);assert.match(r.location,new RegExp(reference))});
r=await guest.request('confirmation',undefined,{ref:reference});check('Receipt contains correct totals and group details',()=>{assert.match(r.html,/\$9\.60/);assert.match(r.html,/For Rudesh/);assert.match(r.html,/Team breakfast/);assert.match(r.html,/Simulated payment approved/)});
r=await guest.request('cart');check('Successful order clears bag',()=>assert.match(r.html,/Your bag is empty/));
const other=new Client();await other.request('register');await other.post('register',{action:'register',name:'Other customer',email:`other-${Date.now()}@example.test`,password:'OtherTest-Password!',privacy:'yes'});
r=await other.request('track',undefined,{ref:reference});check('Another customer cannot read the order',()=>{assert.equal(r.status,404);assert.doesNotMatch(r.html,/0412345678/)});
const staff=new Client();await staff.request('login');
r=await staff.post('login',{action:'login',email:"' OR 1=1 --",password:'bad'});check('SQL injection sign-in rejected',()=>assert.equal(r.status,422));
await staff.request('login');r=await staff.post('login',{action:'login',email:staffEmail,password:staffPassword});check('Staff login',()=>{assert.equal(r.status,303);assert.match(r.location,/staff/)});
r=await staff.request('staff');check('Staff sees customer order',()=>assert.match(r.html,new RegExp(reference)));
const orderCard=r.html.split('<article class="queue-card panel">').find(x=>x.includes(reference));const orderId=field(orderCard,'order_id');
r=await staff.post('staff',{action:'order_status',order_id:orderId,status:'collected'});check('Skipping preparation states rejected',()=>assert.equal(r.status,422));
for(const status of ['preparing','ready','collected']) {await staff.request('staff');r=await staff.post('staff',{action:'order_status',order_id:orderId,status});check(`Staff advances order to ${status}`,()=>assert.equal(r.status,303));}
await staff.request('staff');r=await staff.post('staff',{action:'order_status',order_id:orderId,status:'cancelled'});check('Completed order cannot be cancelled',()=>assert.equal(r.status,422));
r=await guest.request('track',undefined,{ref:reference});check('Customer tracking reflects collected status',()=>assert.match(r.html,/>Collected</));
// A separate order exercises cancellation and simulated refund.
await guest.request('menu');await guest.post('menu',{action:'add_cart',item_id:'2',quantity:'1'});r=await guest.request('checkout');
const cancelPayload={...payload,checkout_key:field(r.html,'checkout_key'),expected_total:field(r.html,'expected_total')};
r=await guest.post('checkout',cancelPayload);const cancelledRef=new URL(r.location,base).searchParams.get('ref');
r=await staff.request('staff');const cancelCard=r.html.split('<article class="queue-card panel">').find(x=>x.includes(cancelledRef));
r=await staff.post('staff',{action:'order_status',order_id:field(cancelCard,'order_id'),status:'cancelled'});check('Cancellation succeeds',()=>assert.equal(r.status,303));
r=await guest.request('track',undefined,{ref:cancelledRef});check('Cancellation records simulated refund',()=>assert.match(r.html,/Simulated payment refunded/));
// Save and restore a catalogue item to verify availability and historical prices.
await staff.request('manage');
const menuPayload={action:'menu_save',item_id:'1',name:'Flat white',description:'A double espresso, silky steamed milk and a little morning magic.',allergens:'Milk',price:'5.80',category:'Coffee'};
r=await staff.post('manage',menuPayload);check('Staff edits catalogue',()=>assert.equal(r.status,303));
r=await guest.request('menu');check('Sold-out item shown',()=>assert.match(r.html,/Taking a little break/));
r=await guest.post('menu',{action:'add_cart',item_id:'1',quantity:'1'});check('Sold-out item cannot be ordered',()=>assert.equal(r.status,422));
r=await guest.request('track',undefined,{ref:reference});check('Historical order keeps original price',()=>assert.match(r.html,/\$9\.60/));
await staff.request('manage');await staff.post('manage',{...menuPayload,price:'4.80',available:'on'});
await guest.request('orders');await guest.post('orders',{action:'logout'});r=await guest.request('orders');check('Logout removes account access',()=>assert.equal(r.status,303));
const brute=new Client();for(let i=0;i<11;i++){await brute.request('login');r=await brute.post('login',{action:'login',email:`missing-${customerEmail}`,password:'wrong'});}check('Database-backed login rate limit',()=>assert.equal(r.status,429));
mkdirSync('artifacts',{recursive:true});writeFileSync('artifacts/integration-results.json',JSON.stringify({executedAt:new Date().toISOString(),base,customerEmail,reference,cancelledRef,passed:results.length,results},null,2));
console.log(`\n${results.length} integration checks passed.`);
