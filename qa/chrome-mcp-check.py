"""Run a small browser check through an isolated Chrome DevTools MCP server."""
import json
import re
import subprocess
from pathlib import Path

root = Path(__file__).resolve().parent.parent
browser = subprocess.Popen(
    f'npx -y chrome-devtools-mcp@1.10.1 --isolated --headless --no-usage-statistics --workspace="{root}"',
    shell=True, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.DEVNULL,
    text=True, encoding='utf-8', bufsize=1,
)

def request(method, params=None):
    request.id += 1
    browser.stdin.write(json.dumps({'jsonrpc': '2.0', 'id': request.id, 'method': method, 'params': params or {}}) + '\n')
    browser.stdin.flush()
    while True:
        line = browser.stdout.readline()
        if not line:
            raise RuntimeError(f'Chrome MCP ended while waiting for {method}')
        message = json.loads(line)
        if message.get('id') == request.id:
            if 'error' in message:
                raise RuntimeError(message['error'])
            return message['result']
request.id = 0

def call(name, arguments):
    result = request('tools/call', {'name': name, 'arguments': arguments})
    if result.get('isError'):
        raise RuntimeError(result)
    return '\n'.join(item['text'] for item in result['content'] if item['type'] == 'text')

booking_id = None
page_id = None
try:
    request('initialize', {'protocolVersion': '2025-06-18', 'capabilities': {}, 'clientInfo': {'name': 'restaurant-qa', 'version': '1.0'}})
    browser.stdin.write(json.dumps({'jsonrpc': '2.0', 'method': 'notifications/initialized'}) + '\n')
    browser.stdin.flush()
    tools = request('tools/list')
    assert any(tool['name'] == 'new_page' for tool in tools['tools'])
    page = call('new_page', {'url': 'http://127.0.0.1:18087/'})
    page_id = int(re.search(r'(\d+): .*http://127\.0\.0\.1:18087/', page).group(1))
    home = call('evaluate_script', {'pageId': page_id, 'function': '() => ({title: document.title, heading: document.querySelector("h1")?.innerText, overflow: document.documentElement.scrollWidth > innerWidth})'})
    assert 'Good food.' in home and '"overflow":false' in home, home
    for width in (375, 768, 1024, 1440):
        call('emulate', {'pageId': page_id, 'viewport': f'{width}x844x1,mobile,touch'})
        layout = call('evaluate_script', {'pageId': page_id, 'function': '() => ({overflow: document.documentElement.scrollWidth > innerWidth, width: innerWidth})'})
        assert '"overflow":false' in layout and f'"width":{width}' in layout, layout
        if width == 375:
            call('take_screenshot', {'pageId': page_id, 'filePath': str(root / 'qa' / 'chrome-home-375.png')})
    call('take_screenshot', {'pageId': page_id, 'filePath': str(root / 'qa' / 'chrome-final-home.png'), 'fullPage': True})

    call('navigate_page', {'pageId': page_id, 'type': 'url', 'url': 'http://127.0.0.1:18087/menu/'})
    menu = call('evaluate_script', {'pageId': page_id, 'function': '() => { document.querySelector("[data-category=bowls]").click(); return {visible: [...document.querySelectorAll(".menu-card")].filter(card => !card.hidden).length, count: document.querySelector("#menu-count").textContent}; }'})
    assert '"visible":3' in menu and '3 dishes' in menu, menu
    call('emulate', {'pageId': page_id, 'viewport': '390x844x1,mobile,touch'})
    mobile = call('evaluate_script', {'pageId': page_id, 'function': '() => ({overflow: document.documentElement.scrollWidth > innerWidth, width: innerWidth})'})
    assert '"overflow":false' in mobile and '"width":390' in mobile, mobile
    navigation = call('evaluate_script', {'pageId': page_id, 'function': '() => { const menu = document.querySelector(".mobile-menu"); menu.querySelector("summary").click(); return {open: menu.open, links: menu.querySelectorAll("nav a").length}; }'})
    assert '"open":true' in navigation and '"links":3' in navigation, navigation
    call('take_screenshot', {'pageId': page_id, 'filePath': str(root / 'qa' / 'chrome-mobile-nav.png')})

    call('navigate_page', {'pageId': page_id, 'type': 'url', 'url': 'http://127.0.0.1:18087/reservations/'})
    reservation = call('take_snapshot', {'pageId': page_id})
    assert 'Confirm reservation' in reservation and 'Email address' in reservation, reservation
    call('take_screenshot', {'pageId': page_id, 'filePath': str(root / 'qa' / 'chrome-final-reservations-mobile.png'), 'fullPage': True})
    call('evaluate_script', {'pageId': page_id, 'function': '''() => { const f = document.querySelector("#reservation-form"); const day = new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10); for (const [name, value] of Object.entries({date: day, time: "18:30", party: "2", name: "Chrome QA Guest", email: "chrome-qa@example.test", phone: "+63 912 345 6789", note: "Browser test"})) f.elements[name].value = value; f.requestSubmit(); return true; }'''})
    call('wait_for', {'pageId': page_id, 'text': ['Your table is reserved.']})
    confirmation = call('evaluate_script', {'pageId': page_id, 'function': '() => ({heading: document.querySelector("h2")?.innerText, url: location.href})'})
    booking_id = int(re.search(r'booking=RES-(\d+)', confirmation).group(1))
    assert 'Your table is reserved.' in confirmation, confirmation
    print('Chrome MCP passed: home, menu filter, mobile navigation, mobile layout, reservation form, and confirmation.')
finally:
    if not booking_id and page_id:
        try:
            current = call('evaluate_script', {'pageId': page_id, 'function': '() => location.href'})
            match = re.search(r'booking=RES-(\d+)', current)
            if match:
                booking_id = int(match.group(1))
        except Exception:
            pass
    browser.terminate()
    browser.wait(timeout=10)
    if booking_id:
        subprocess.run(['docker', 'compose', '-p', 'salo-reservations', 'run', '--rm', '-T', 'wpcli', 'wp', 'post', 'delete', str(booking_id), '--force', '--path=/var/www/html'], cwd=root, check=True)
