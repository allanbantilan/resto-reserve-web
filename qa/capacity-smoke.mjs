import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';

const site = process.env.SITE_URL || 'http://127.0.0.1:18087';
const project = process.env.COMPOSE_PROJECT_NAME || 'salo-reservations';
const html = await (await fetch(`${site}/reservations/`)).text();
const nonce = html.match(/name="salo_nonce" value="([^"]+)"/)?.[1];
assert.ok(nonce, 'reservation nonce is present');

const date = new Date(Date.now() + 37 * 86400000).toISOString().slice(0, 10);
const form = new URLSearchParams({action:'salo_reserve', salo_nonce:nonce, date, time:'15:30', party:'2', name:'QA Capacity Guest', email:'capacity@example.test', phone:'+63 912 345 6789'});
const ids = [];
try {
  const responses = await Promise.all(Array.from({length:12}, () => fetch(`${site}/wp-admin/admin-post.php`, {method:'POST', body:form, redirect:'manual'})));
  const locations = responses.map(response => {
    assert.equal(response.status, 302);
    return response.headers.get('location');
  });
  for (const location of locations) {
    const id = location.match(/booking=RES-(\d+)/)?.[1];
    if (id) ids.push(id);
    else assert.match(location, /error=full/, `unexpected booking result: ${location}`);
  }
  assert.ok(ids.length > 0 && ids.length <= 10, `slot accepted ${ids.length} concurrent bookings`);
  assert.ok(locations.length - ids.length >= 2, 'excess bookings were declined');
  console.log(`Concurrent capacity passed: ${ids.length} accepted, ${locations.length - ids.length} declined.`);
} finally {
  if (ids.length) {
    const result = spawnSync('docker', ['compose','-p',project,'run','--rm','-T','wpcli','wp','post','delete',...ids,'--force','--path=/var/www/html'], {stdio:'inherit', shell:false});
    assert.equal(result.status, 0, 'test bookings are removed');
  }
}
