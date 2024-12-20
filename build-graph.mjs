import { promises as fs } from 'fs';
import path from 'path';
import { globby } from 'globby';

(async () => {
  // Find all .php files
  const files = await globby(['**/*.php', '!node_modules', '!node_modules/**/*']);
  console.log('Files found:', files);

  const nodes = [];
  const links = [];
  const fileSet = new Set(files);

  // Create a map from filename (like "somefile.php") to its full paths.
  // If you have multiple files with the same filename in different folders, this can lead to ambiguity.
  // This example picks the first match.
  const filenameMap = new Map();
  for (const f of files) {
    const base = path.basename(f);
    // If multiple files have the same basename, this will overwrite. If that’s a concern,
    // you might need a more complex lookup strategy.
    if (!filenameMap.has(base)) {
      filenameMap.set(base, f);
    }
  }

  // Regex to find .php references anywhere in the file
  // This matches sequences of characters (letters, digits, underscore, hyphen, slash, dot)
  // ending in .php, which might look like "views/seller_store_view.php" or "config.php".
  const phpFileRegex = /[A-Za-z0-9_\-\/\.]+\.php/g;

  for (const filePath of files) {
    const content = await fs.readFile(filePath, 'utf8');

    // Find all .php occurrences
    let match;
    while ((match = phpFileRegex.exec(content)) !== null) {
      const dep = match[0]; // The matched .php filename/path
      const depBase = path.basename(dep); // Get just the filename, e.g. "seller_store_view.php"

      // Check if we have a file with that filename
      if (filenameMap.has(depBase)) {
        const targetFile = filenameMap.get(depBase);
        // Create a link if we found a matching file
        // Avoid linking file to itself
        if (targetFile !== filePath) {
          links.push({ source: filePath, target: targetFile });
        }
      }
    }
  }

  for (const f of fileSet) {
    nodes.push({ id: f });
  }

  const graphData = { nodes, links };
  await fs.writeFile('graphData.json', JSON.stringify(graphData, null, 2), 'utf8');
  console.log('graphData.json created!');
})();
