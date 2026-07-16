import { createHash } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';

function digestFile(filePath) {
  return createHash('sha256').update(fs.readFileSync(filePath)).digest('hex');
}

export function filePathType(stat) {
  if (stat.isDirectory()) return 'directory';
  if (stat.isFile()) return 'file';
  if (stat.isSymbolicLink()) return 'symlink';
  throw new Error('Scoped E2E artifacts support only directories, files, and symlinks.');
}

export function collectFileEntries(rootPath, relativeRoot) {
  if (!fs.existsSync(rootPath)) return [{ path: relativeRoot, type: 'missing' }];

  const entries = [];
  const visit = (absolutePath, relativePath) => {
    const stat = fs.lstatSync(absolutePath);
    const type = filePathType(stat);
    const entry = { path: relativePath, type, mode: stat.mode & 0o777 };
    if (type === 'file') entry.digest = digestFile(absolutePath);
    if (type === 'symlink') entry.target = fs.readlinkSync(absolutePath);
    entries.push(entry);
    if (type === 'directory') {
      for (const name of fs.readdirSync(absolutePath).sort()) {
        visit(path.join(absolutePath, name), path.join(relativePath, name));
      }
    }
  };
  visit(rootPath, relativeRoot);
  return entries;
}

export function fingerprintFileEntries(entries) {
  return createHash('sha256').update(JSON.stringify(entries)).digest('hex');
}

export function fileEntryDepth(relativePath) {
  return relativePath.split(path.sep).length;
}
