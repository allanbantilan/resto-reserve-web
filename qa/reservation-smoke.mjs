import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';

const site = process.env.SITE_URL || 'http://127.0.0.1:18087';
const project = process.env.COMPOSE_PROJECT_NAME || 'salo-reservations';
const mailpit = process.env.MAILPIT_URL || 'http://127.0.0.1:18088';
const page = await fetch(`${site}/reservations/`);
assert.equal(page.status, 200);
const html = await page.text();
const nonce = html.match(/name="salo_nonce" value="([^"]+)"/)?.[1];
assert.ok(nonce, 'reservation form nonce is present');

const booking = {
  action: 'salo_reserve', salo_nonce: nonce,
  date: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10),
  time: '18:30', party: '2', name: 'QA Guest',
  email: 'qa@example.test', phone: '+63 912 345 6789', note: 'Automated test'
};
const submit = data => fetch(`${site}/wp-admin/admin-post.php`, {method:'POST', body:new URLSearchParams(data), redirect:'manual'});
let id;
try {
  const invalid = await submit({...booking, date:'2020-01-01'});
  assert.equal(invalid.status, 302);
  assert.match(invalid.headers.get('location'), /error=date/);

  const valid = await submit(booking);
  assert.equal(valid.status, 302);
  const location = valid.headers.get('location');
  id = Number(location.match(/booking=RES-(\d+)/)?.[1]);
  assert.ok(id, 'booking reference is returned');
  assert.match(location, /proof=/);
  assert.match(await (await fetch(location)).text(), /Your table is reserved/);
  assert.doesNotMatch(await (await fetch(`${site}/reservations/?booking=RES-${id}`)).text(), /Your table is reserved/);
  const inbox = await (await fetch(`${mailpit}/api/v1/messages`)).json();
  const subjects = inbox.messages.map(message => message.Subject);
  assert.ok(subjects.includes(`Reservation confirmed RES-${String(id).padStart(5, '0')}`), 'guest email is captured');
  assert.ok(subjects.includes(`New table reservation RES-${String(id).padStart(5, '0')}`), 'admin email is captured');
  console.log(`Reservation flow passed (RES-${id}).`);
} finally {
  if (id) {
    const args = ['compose','-p',project,'run','--rm','-T','wpcli','wp','post','delete',String(id),'--force','--path=/var/www/html'];
    const result = spawnSync('docker', args, {stdio:'inherit', shell:false});
    assert.equal(result.status, 0, 'test booking is removed');
  }
}
