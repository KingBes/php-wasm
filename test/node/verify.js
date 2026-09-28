// 用 Node 的 WebAssembly 引擎实例化并校验 PHP 生成的模块。
// 用法: node verify.js <module.wasm> <spec.json>
// 输出: 一行 JSON { ok: bool, results: [...] }

const fs = require('fs');

function toJs(type, value) {
    if (type === 'i64') return BigInt(value);
    return value;
}

function fromJs(type, value) {
    if (type === null) return value === undefined ? 'undefined' : String(value);
    if (type === 'i64') return BigInt(value).toString();
    if (type === 'f32' || type === 'f64') return String(Number(value));
    return String(Number(value));
}

function buildImports(spec) {
    const importObject = {};
    for (const [mod, items] of Object.entries(spec.imports || {})) {
        importObject[mod] = {};
        for (const [name, stub] of Object.entries(items)) {
            if (stub.global) {
                importObject[mod][name] = new WebAssembly.Global(
                    { value: stub.type, mutable: !!stub.mutable },
                    stub.value === undefined ? 0 : stub.value
                );
            } else {
                const resultType = (stub.results && stub.results[0]) || null;
                importObject[mod][name] = (...args) => {
                    if (stub.returns === undefined || resultType === null) return undefined;
                    return toJs(resultType, stub.returns);
                };
            }
        }
    }
    return importObject;
}

function runCheck(instance, check) {
    const kind = check.kind || 'call';

    if (kind === 'memory') {
        const mem = instance.exports[check.memory];
        if (!mem) return { kind, ok: false, error: `memory export not found: ${check.memory}` };
        const bytes = new Uint8Array(mem.buffer, check.offset, check.length);
        const actual = Buffer.from(bytes).toString('hex');
        const ok = actual === check.expectHex;
        return { kind, ok, expected: check.expectHex, actual };
    }

    if (kind === 'global') {
        const g = instance.exports[check.global];
        if (!g) return { kind, ok: false, error: `global export not found: ${check.global}` };
        const actual = String(g.value);
        const ok = actual === String(check.expect);
        return { kind, global: check.global, ok, expected: String(check.expect), actual };
    }

    if (kind === 'tableSize') {
        const tbl = instance.exports[check.table];
        if (!tbl) return { kind, ok: false, error: `table export not found: ${check.table}` };
        const actual = String(tbl.length);
        const ok = actual === String(check.expect);
        return { kind, table: check.table, ok, expected: String(check.expect), actual };
    }

    const fn = instance.exports[check.export];
    if (typeof fn !== 'function') {
        return { kind, export: check.export, ok: false, error: `export not found: ${check.export}` };
    }

    const sig = check.sig || {};
    const params = sig.params || [];
    const args = (check.args || []).map((a, i) => toJs(params[i] || 'i32', a));
    let value;
    try {
        value = fn(...args);
    } catch (e) {
        return { kind, export: check.export, ok: false, error: String(e) };
    }

    const resultType = sig.results && sig.results.length > 0 ? sig.results[0] : null;
    const actual = fromJs(resultType, value);
    const expected = fromJs(resultType, check.expect === null || check.expect === undefined ? undefined : check.expect);
    const ok = resultType === null
        ? value === undefined
        : actual === expected;
    return { kind, export: check.export, ok, expected, actual };
}

async function main() {
    const [wasmPath, specPath] = process.argv.slice(2);
    const spec = JSON.parse(fs.readFileSync(specPath, 'utf8'));
    const bytes = fs.readFileSync(wasmPath);

    const { instance } = await WebAssembly.instantiate(bytes, buildImports(spec));

    const results = [];
    let ok = true;
    for (const check of spec.checks || []) {
        const result = runCheck(instance, check);
        if (!result.ok) ok = false;
        results.push(result);
    }

    process.stdout.write(JSON.stringify({ ok, results }));
}

main().catch((e) => {
    process.stdout.write(JSON.stringify({ ok: false, error: String(e && e.stack ? e.stack : e) }));
});