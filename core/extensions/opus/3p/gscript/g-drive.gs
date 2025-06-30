function _driveConfig() {
  return {
    isSharedDrive: false,
    isTopFolder: false,
    globalSerialNo: 0,
    topFolderName: '',
    topFolderId: '',
  }
}

function _getSharedDrive(name) {
  return Drive.Drives.list({ q: 'name = "' + name + '"', supportsAllDrives: true }).drives.pop()
}

function _getFoldersOf(folder) {
  var result = []

  var subFolders = folder.getFolders();
  while (subFolders.hasNext())
    result.push(subFolders.next())

  result.sort(__sortAscending)
  return result
}

function _getFilesOf(folder) {
  if (isTopFolder && isSharedDrive) {
    return Drive.Files.list({
      driveId: topFolderId, corpora: "drive",
      includeItemsFromAllDrives: true, supportsAllDrives: true
    }).files
  }

  var result = []
  var files = folder.getFiles()

  while (files.hasNext())
    result.push(files.next())

  result.sort(__sortAscending)
  return result;
}

function _sortAscending(item1, item2) {
  var a = item1.getName(), b = item2.getName()
  return a > b ? 1 : (a < b ? -1 : 0)
}
