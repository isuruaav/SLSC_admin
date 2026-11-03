import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';

test('PHP registration validation, persistence, idempotency and rate limiting', () => {
    const output = execFileSync('php', ['-d', 'xdebug.log=', '-d', 'xdebug.mode=off', 'tests/registration-backend.php'], { encoding: 'utf8' });
    assert.match(output, /backend checks passed/);
});
