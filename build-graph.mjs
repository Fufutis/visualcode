import { promises as fs } from 'fs';
import path from 'path';
import { globby } from 'globby';

async function buildGraphData() {
  // Search for both .js and .php files, excluding node_modules
  const files = await globby(['**/*.js', '**/*.php', '!node_modules', '!node_modules/**/*']);
  console.log('Files found:', files);

  const nodes = [];
  const links = [];
  const fileSet = new Set();

  for (const filePath of files) {
    const content = await fs.readFile(filePath, 'utf8');
    fileSet.add(filePath);

    // Determine file type by extension
    const ext = path.extname(filePath);

    let dependencies = [];
    if (ext === '.js') {
      // JS: Detect ESM imports and CommonJS requires
      const importRegex = /import\s+(?:[\w*{}\s,]+from\s+)?['"]([^'"]+)['"]/g;
      const requireRegex = /require\(['"]([^'"]+)['"]\)/g;

      let match;
      while ((match = importRegex.exec(content)) !== null) {
        dependencies.push(match[1]);
      }

      while ((match = requireRegex.exec(content)) !== null) {
        dependencies.push(match[1]);
      }
    } else if (ext === '.php') {
      // PHP: Detect includes and requires
      const phpIncludeRegex = /(include|include_once|require|require_once)\s*\(?['"]([^'"]+)['"]\)?/g;
      let match;
      while ((match = phpIncludeRegex.exec(content)) !== null) {
        dependencies.push(match[2]); // match[2] is the file path in the statement
      }
    }

    // Resolve relative paths for both JS and PHP files
    dependencies = dependencies.map(dep => {
      // Only handle relative paths (e.g. './something.js' or './config.php')
      // Non-relative paths (like imports of node modules or PHP extensions) are ignored
      if (dep.startsWith('.')) {
        const depPath = path.resolve(path.dirname(path.resolve(process.cwd(), filePath)), dep);
        return path.relative(process.cwd(), depPath);
      } else {
        return null;
      }
    }).filter(Boolean);

    // Create links only if the dependency file is in the set of discovered files
    for (const dep of dependencies) {
      if (files.includes(dep)) {
        links.push({ source: filePath, target: dep });
      }
    }
  }

  // Create nodes
  for (const f of fileSet) {
    nodes.push({ id: f });
  }

  return { nodes, links };
}

(async () => {
  try {
    console.log('Working directory:', process.cwd());
    const graphData = await buildGraphData();
    console.log('Graph Data:', graphData);
    await fs.writeFile('graphData.json', JSON.stringify(graphData, null, 2), 'utf8');
    console.log('graphData.json created!');
  } catch (err) {
    console.error('Error building graph data:', err);
  }
})();
