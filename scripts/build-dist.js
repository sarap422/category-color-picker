const fs = require('fs-extra');
const path = require('path');

const srcDir = path.join(__dirname, '..', 'src');
const distDir = path.join(__dirname, '..', 'dist', 'category-color-picker');

async function buildDist() {
  try {
    await fs.remove(distDir);
    await fs.ensureDir(distDir);
    await fs.copy(srcDir, distDir);
    console.log('✅ Build completed successfully!');
  } catch (error) {
    console.error('❌ Build failed:', error);
  }
}

buildDist();