/*
  * [Drive] "Catalog" Lists all files and sub-folders from a Shared Drive / Folder in Google Drive
  * A part of Opus for GW by AmadeusWeb.com
  * Developed since 2024/2025 and Copyrighted by Imran Ali Namazi
  * TODO
    * broken in refactoring... - show X student how to fix

  Begun in Dec 2024 as documented here:
    https://github.com/yieldmore/google-apps-scripts/blob/master/folder-indexer.gs

  Adapted from Code written by @hubgit https://gist.github.com/hubgit/3755293
  Updated since DocsList is deprecated  https://ctrlq.org/code/19854-list-files-in-google-drive-folder
  Bluexm: added recursion on subfolders - SO: https://webapps.stackexchange.com/a/142584
*/



var driveObj = _driveConfig()

function _beginScaningDrives(cfg) {
  var parents = []

  if (namesSheet != null) {
    rows = namesSheet.getRange(2, 1, namesSheet.getLastRow() - 1, 2).getValues()
    rows.forEach(function (item) { parents.push({ name: item[0], type: item[1] }) })
    sheetFile.setActiveSheet(namesSheet)
  } else {
    var parentType = 'folder', parentName = ''

    try {
      var sharedDrive = Drive.Files.get(sheetFile.getId()).parents.pop()
      topFolderId = sharedDrive.id
      parentType = 'shared'
    } catch {
      var parentFolder = DriveApp.getFileById(sheetFile.getId()).getParents().next();
      topFolderId = parentFolder.getId()
      parentName = parentFolder.getName()
    }

    parents.push({ name: parentName, type: parentType })
  }

  globalSerialNo = 0
  parents.forEach(ScanDrive)
}

function __scanDrive(parent, nameIndex) {
  if (parent.name.substring(0, 1) == '|') {
    Logger.log('Skipping: ' + parent.name)
    return
  }

  setCount('...', nameIndex)

  isSharedDrive = parent.type == 'shared'
  topFolderName = parent.name

  if (isSharedDrive)
    topFolderId = getSharedDrive(topFolderName).id

  Logger.log('Scanning ' + (isSharedDrive ? 'Shared' : 'Folder in ') + ' Drive: ' + topFolderName)

  sheet = sheetFile.getSheetByName(parent.name)
  if (sheet == null) {
    sheet = sheetFile.insertSheet(parent.name)
  }

  ScanFolder(topFolderName, '', 0, '')
  _sanitizeSheet(sheet)
  setCount(sheet.getLastRow() - 1, nameIndex)
}

function __scanFolder(folderName, relativeFolderName, level, indent) {
  isTopFolder = topFolderName == folderName
  if (isTopFolder) {
    sheet.clearContents()

    sheet.appendRow([
      "#sno",
      "_level",
      "_type",
      "_indent",
      "name",
      "_description",
      "parent",
      "_drive-link",
      //ignored
      "date-last-updated",
      "size",
      "full-parent",
    ])

    Logger.log("REBUILDING SHEET In: " + sheetFile.getName())
  }

  Logger.log("SCANNING: " + folderName)

  var relativeFolder = folderName + (isTopFolder ? '' : ' « ' + relativeFolderName)

  var folder = isTopFolder && isSharedDrive
    ? DriveApp.getFolderById(topFolderId)
    : DriveApp.getFoldersByName(folderName).next()

  var subFolders = getFoldersOf(folder)

  var folderIndex = 0
  while (folderIndex < subFolders.length) {
    var item = subFolders[folderIndex]
    folderIndex += 1
    globalSerialNo += 1

    var name = item.getName()
    var skipChildren = name.endsWith('__')
    if (skipChildren) name = name.replace('__', '')

    var data = [
      globalSerialNo,
      level,
      'folder',
      indent + '/ ',
      name,
      item.getDescription(),
      folderName,
      item.getUrl(),
      isTopFolder && isSharedDrive ? item.ModifiedTimeRaw : item.getLastUpdated(),
      item.getSize(),
      relativeFolder,
    ]

    sheet.appendRow(data)

    if (!skipChildren)
      ScanFolder(name, folderName + ' « ' + relativeFolder, level + 1, indent + '  ')
  }

  var files = getFilesOf(folder)

  var fileIndex = 0
  while (fileIndex < files.length) {
    var item = files[fileIndex]
    fileIndex += 1
    globalSerialNo += 1

    var data = [
      globalSerialNo,
      level,
      'file',
      indent + '/ ',
      item.getName(),
      item.getDescription(),
      folderName,
      isTopFolder && isSharedDrive ? item.WebViewLink : item.getUrl(),
      isTopFolder && isSharedDrive ? item.ModifiedTimeRaw : item.getLastUpdated(),
      item.getSize(),
      relativeFolder,
    ]

    sheet.appendRow(data)
  }
}
/*
  * [Drive] "Catalog" Lists all files and sub-folders from a Shared Drive / Folder in Google Drive
  * A part of Opus for GW by AmadeusWeb.com
  * Developed since 2024/2025 and Copyrighted by Imran Ali Namazi
  * TODO
    * broken in refactoring... - show X student how to fix

  Begun in Dec 2024 as documented here:
    https://github.com/yieldmore/google-apps-scripts/blob/master/folder-indexer.gs

  Adapted from Code written by @hubgit https://gist.github.com/hubgit/3755293
  Updated since DocsList is deprecated  https://ctrlq.org/code/19854-list-files-in-google-drive-folder
  Bluexm: added recursion on subfolders - SO: https://webapps.stackexchange.com/a/142584
*/



var driveObj = _driveConfig()

function _beginScaningDrives(cfg) {
  var parents = []

  if (namesSheet != null) {
    rows = namesSheet.getRange(2, 1, namesSheet.getLastRow() - 1, 2).getValues()
    rows.forEach(function (item) { parents.push({ name: item[0], type: item[1] }) })
    sheetFile.setActiveSheet(namesSheet)
  } else {
    var parentType = 'folder', parentName = ''

    try {
      var sharedDrive = Drive.Files.get(sheetFile.getId()).parents.pop()
      topFolderId = sharedDrive.id
      parentType = 'shared'
    } catch {
      var parentFolder = DriveApp.getFileById(sheetFile.getId()).getParents().next();
      topFolderId = parentFolder.getId()
      parentName = parentFolder.getName()
    }

    parents.push({ name: parentName, type: parentType })
  }

  globalSerialNo = 0
  parents.forEach(ScanDrive)
}

function __scanDrive(parent, nameIndex) {
  if (parent.name.substring(0, 1) == '|') {
    Logger.log('Skipping: ' + parent.name)
    return
  }

  setCount('...', nameIndex)

  isSharedDrive = parent.type == 'shared'
  topFolderName = parent.name

  if (isSharedDrive)
    topFolderId = getSharedDrive(topFolderName).id

  Logger.log('Scanning ' + (isSharedDrive ? 'Shared' : 'Folder in ') + ' Drive: ' + topFolderName)

  sheet = sheetFile.getSheetByName(parent.name)
  if (sheet == null) {
    sheet = sheetFile.insertSheet(parent.name)
  }

  ScanFolder(topFolderName, '', 0, '')
  _sanitizeSheet(sheet)
  setCount(sheet.getLastRow() - 1, nameIndex)
}

function __scanFolder(folderName, relativeFolderName, level, indent) {
  isTopFolder = topFolderName == folderName
  if (isTopFolder) {
    sheet.clearContents()

    sheet.appendRow([
      "#sno",
      "_level",
      "_type",
      "_indent",
      "name",
      "_description",
      "parent",
      "_drive-link",
      //ignored
      "date-last-updated",
      "size",
      "full-parent",
    ])

    Logger.log("REBUILDING SHEET In: " + sheetFile.getName())
  }

  Logger.log("SCANNING: " + folderName)

  var relativeFolder = folderName + (isTopFolder ? '' : ' « ' + relativeFolderName)

  var folder = isTopFolder && isSharedDrive
    ? DriveApp.getFolderById(topFolderId)
    : DriveApp.getFoldersByName(folderName).next()

  var subFolders = getFoldersOf(folder)

  var folderIndex = 0
  while (folderIndex < subFolders.length) {
    var item = subFolders[folderIndex]
    folderIndex += 1
    globalSerialNo += 1

    var name = item.getName()
    var skipChildren = name.endsWith('__')
    if (skipChildren) name = name.replace('__', '')

    var data = [
      globalSerialNo,
      level,
      'folder',
      indent + '/ ',
      name,
      item.getDescription(),
      folderName,
      item.getUrl(),
      isTopFolder && isSharedDrive ? item.ModifiedTimeRaw : item.getLastUpdated(),
      item.getSize(),
      relativeFolder,
    ]

    sheet.appendRow(data)

    if (!skipChildren)
      ScanFolder(name, folderName + ' « ' + relativeFolder, level + 1, indent + '  ')
  }

  var files = getFilesOf(folder)

  var fileIndex = 0
  while (fileIndex < files.length) {
    var item = files[fileIndex]
    fileIndex += 1
    globalSerialNo += 1

    var data = [
      globalSerialNo,
      level,
      'file',
      indent + '/ ',
      item.getName(),
      item.getDescription(),
      folderName,
      isTopFolder && isSharedDrive ? item.WebViewLink : item.getUrl(),
      isTopFolder && isSharedDrive ? item.ModifiedTimeRaw : item.getLastUpdated(),
      item.getSize(),
      relativeFolder,
    ]

    sheet.appendRow(data)
  }
}
