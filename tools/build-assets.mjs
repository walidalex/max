import { cp, copyFile, mkdir, readdir} from 'node:fs/promises';
import {dirname, join} from 'node:path';

const root = new URL('../', import.meta.url).pathname.replace(/^\/(.:\/)/, '$1');
const output = join(root, 'public', 'assets', 'vendor');
const files = [
  ['node_modules/@tabler/core/dist/css/tabler.rtl.min.css', 'tabler/tabler.rtl.min.css'],
  ['node_modules/@tabler/core/dist/js/tabler.min.js', 'tabler/tabler.min.js'],
  ['node_modules/@tabler/icons-webfont/dist/tabler-icons.min.css', 'tabler-icons/tabler-icons.min.css'],
  ['node_modules/sweetalert2/dist/sweetalert2.min.css', 'sweetalert2/sweetalert2.min.css'],
  ['node_modules/sweetalert2/dist/sweetalert2.all.min.js', 'sweetalert2/sweetalert2.all.min.js'],
  ['node_modules/tom-select/dist/css/tom-select.bootstrap5.min.css', 'tom-select/tom-select.bootstrap5.min.css'],
  ['node_modules/tom-select/dist/js/tom-select.complete.min.js', 'tom-select/tom-select.complete.min.js'],
  ['node_modules/datatables.net/js/dataTables.min.js', 'datatables/dataTables.min.js'],
  ['node_modules/datatables.net-bs5/js/dataTables.bootstrap5.min.js', 'datatables/dataTables.bootstrap5.min.js'],
  ['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'datatables/dataTables.bootstrap5.min.css'],
];

await mkdir(output, {recursive: true});
await Promise.all(files.map(async ([source, target]) => {
  const destination = join(output, target);
  await mkdir(dirname(destination), {recursive: true});
  await copyFile(join(root, source), destination);
}));

const fontSource = join(root, 'node_modules', '@tabler', 'icons-webfont', 'dist', 'fonts');
const fontOutput = join(output, 'tabler-icons', 'fonts');
await mkdir(fontOutput, {recursive: true});
for (const file of await readdir(fontSource)) await copyFile(join(fontSource, file), join(fontOutput, file));
console.log('Frontend assets copied to public/assets/vendor.');
