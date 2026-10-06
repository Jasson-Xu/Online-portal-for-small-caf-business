import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {mkdirSync,writeFileSync} from 'node:fs';
const base=process.env.TEST_BASE_URL||'http://127.0.0.1:8080/';
const php=process.env.TEST_PHP||'php';
const phpArgs=process.env.TEST_PHP_INI?['-c',process.env.TEST_PHP_INI]:[];
const fixture=(action,...args)=>execFileSync(php,[...phpArgs,'tests/fault-fixture.php',action,...args],{env:{...process.env,CAFE_FAULT_TEST:'1'},encoding:'utf8'}).trim();
let cookie='',csrf='';
const field=(html,name)=>html.match(new RegExp(`name="${name}" value="([^"]+)"`))[1];
async function req(page,data){const r=await fetch(`${base}index.php?page=${page}`,{method:data?'POST':'GET',redirect:'manual',headers:{Cookie:cookie,...(data?{'Content-Type':'application/x-www-form-urlencoded'}:{})},body:data?new URLSearchParams({csrf,...data}):undefined});const set=r.headers.getSetCookie();if(set.length)cookie=set.map(s=>s.split(';')[0]).join('; ');const html=await r.text();const t=html.match(/name="csrf" value="([^"]+)"/);if(t)csrf=t[1];return{status:r.status,html,location:r.headers.get('location')};}
await req('register');await req('register',{action:'register',name:'Failure Test',email:`failure-${Date.now()}@example.test`,password:'FailureTest-Password!',privacy:'yes'});
await req('menu');await req('menu',{action:'add_cart',item_id:'3',quantity:'1'});
let r=await req('checkout');const slots=[...r.html.matchAll(/<option value="(\d{4}-[^"]+)"/g)].map(m=>m[1]);const slot=slots.at(-1);
const payload={action:'checkout',checkout_key:field(r.html,'checkout_key'),expected_total:field(r.html,'expected_total'),customer_name:'Failure Test',phone:'0412345678',pickup_at:slot,payment_result:'approved',payment_consent:'yes'};
const checks=[];
try {
  fixture('full-slot',slot);const before=fixture('snapshot');r=await req('checkout',payload);assert.equal(r.status,422);assert.match(r.html,/collection time is full/);assert.equal(fixture('snapshot'),before);checks.push('Full slot rejects the order without partial writes');
} finally {fixture('restore-slot',slot);}
try {
  fixture('failure-on');const before=fixture('snapshot');await req('checkout');r=await req('checkout',payload);assert.equal(r.status,503);assert.equal(fixture('snapshot'),before);checks.push('Payment storage failure rolls back order, lines, audit and capacity');
  r=await req('cart');assert.match(r.html,/Cappuccino/);checks.push('Failed transaction retains customer bag');
} finally {fixture('failure-off');}
await req('checkout');r=await req('checkout',payload);assert.equal(r.status,303);checks.push('Same checkout can succeed after failure recovery');
mkdirSync('artifacts',{recursive:true});writeFileSync('artifacts/failure-results.json',JSON.stringify({executedAt:new Date().toISOString(),checks},null,2));console.log(checks.join('\n'));console.log(`${checks.length} failure recovery checks passed.`);
