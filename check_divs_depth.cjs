const fs = require('fs');
const path = 'resources/views/inventario/index.blade.php';

try {
    const content = fs.readFileSync(path, 'utf8');
    const lines = content.split('\n');
    let stack = 0;

    lines.forEach((line, index) => {
        const lineNumber = index + 1;

        // Count opening divs
        const openMatches = (line.match(/<div/g) || []).length;
        // Count closing divs
        const closeMatches = (line.match(/<\/div>/g) || []).length;

        stack += openMatches;
        stack -= closeMatches;

        if (line.includes('id="registro-modal"')) {
            console.log(`Line ${lineNumber} (registro-modal): Depth ${stack} (Expected 2)`);
        }
        if (line.includes('id="detalle-modal"')) {
            console.log(`Line ${lineNumber} (detalle-modal): Depth ${stack} (Expected 2)`);
        }
        if (line.includes('id="fotos-modal"')) {
            console.log(`Line ${lineNumber} (fotos-modal): Depth ${stack} (Expected 2)`);
        }
        if (line.includes('id="preview-fotos-modal"')) {
            console.log(`Line ${lineNumber} (preview-fotos-modal): Depth ${stack} (Expected 2)`);
        }
        if (line.includes('id="ingreso-modal"')) {
            console.log(`Line ${lineNumber} (ingreso-modal): Depth ${stack} (Expected 2)`);
        }
        if (line.includes('id="editar-modal"')) {
            console.log(`Line ${lineNumber} (editar-modal): Depth ${stack} (Expected 2)`);
        }
        if (line.includes('id="precios-modal"')) {
            console.log(`Line ${lineNumber} (precios-modal): Depth ${stack} (Expected 2)`);
        }
        if (line.includes('id="egreso-modal"')) {
            console.log(`Line ${lineNumber} (egreso-modal): Depth ${stack} (Expected 2)`);
        }
        if (line.includes('id="historial-modal"')) {
            console.log(`Line ${lineNumber} (historial-modal): Depth ${stack} (Expected 2)`);
        }
    });

    console.log(`Final Depth: ${stack}`);

} catch (err) {
    console.error(err);
}
