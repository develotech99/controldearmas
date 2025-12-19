const fs = require('fs');
const path = 'resources/views/inventario/index.blade.php';

try {
    const content = fs.readFileSync(path, 'utf8');
    const lines = content.split('\n');
    let stack = [];
    let errors = [];

    lines.forEach((line, index) => {
        const lineNumber = index + 1;

        // Count opening divs
        const openMatches = (line.match(/<div/g) || []).length;
        // Count closing divs
        const closeMatches = (line.match(/<\/div>/g) || []).length;

        for (let i = 0; i < openMatches; i++) {
            stack.push(lineNumber);
        }

        for (let i = 0; i < closeMatches; i++) {
            if (stack.length === 0) {
                errors.push(`Extra closing div at line ${lineNumber}`);
            } else {
                stack.pop();
            }
        }
    });

    if (stack.length > 0) {
        console.log('Unclosed divs found starting at lines:', stack);
    } else if (errors.length > 0) {
        console.log('Errors found:', errors);
    } else {
        console.log('All divs are balanced.');
    }

} catch (err) {
    console.error(err);
}
