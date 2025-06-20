const fs = require('fs-extra');
const path = require('path');
const archiver = require('archiver');

const distDir = path.join(__dirname, '..', 'dist', 'category-color-picker');
const zipPath = path.join(__dirname, '..', 'dist', 'category-color-picker.zip');

async function createZip() {
  try {
    if (await fs.pathExists(zipPath)) {
      await fs.remove(zipPath);
    }

    if (!await fs.pathExists(distDir)) {
      console.error('❌ Distribution directory not found. Run "npm run build" first.');
      process.exit(1);
    }

    const output = fs.createWriteStream(zipPath);
    const archive = archiver('zip', { zlib: { level: 9 } });

    return new Promise((resolve, reject) => {
      output.on('close', () => {
        console.log('✅ ZIP file created successfully!');
        resolve();
      });

      archive.on('error', reject);
      archive.pipe(output);
      archive.directory(distDir, 'category-color-picker');
      archive.finalize();
    });

  } catch (error) {
    console.error('❌ ZIP creation failed:', error);
    process.exit(1);
  }
}

createZip();