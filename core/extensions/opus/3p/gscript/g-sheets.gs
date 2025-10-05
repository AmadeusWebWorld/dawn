function TestSheets() {
  /*
  var wo = _getSheetObject('Demo - Donors from Imran at WiseOwls', 'Contacts Pulled 1')
  Logger.log({ testName: 'WO', expected: 'ok', actual: wo })

  var dupe = _getSheet('Duplicate A for Opus Testing', 'Sheet 1')
  Logger.log({ testName: 'DU', expected: 'fail', actual: dupe })

  var nfe = _getSheet('Duplicate B for Opus Testing', '__NEW')
  Logger.log({ testName: 'NFE', expected: 'fail', actual: nfe })

  var nf = _getSheet('Duplicate B for Opus Testing', '__NEW', 'AMW Opus Demo Project')
  Logger.log({ testName: 'NF', expected: 'ok', actual: nf })
  */

  Logger.log(todayIfWanted('yes'));
}

function todayIfWanted(yes) {
  if (!yes) return ''
  const today = new Date()
  const months = 'Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec'.split(' ')
  return '_' + (today.getDate() + '') + '-' + months[today.getMonth()]
}

//TODO: code and test all usages / failure scenarios
function _getSheet(fileName, sheetName, parentFolder = 'NONE', dontClear = false) {
  var files = DriveApp.getFilesByName(fileName)

  if (!files.hasNext()) {
    if (parentFolder == 'NONE')
      return new ReferenceError('No File: "' + fileName + '" in anywhere in drive.');

    const parent = DriveApp.getFoldersByName(parentFolder).next() //TODO: make this a tech function
    SpreadsheetApp.create(fileName)
    var newFile = DriveApp.getFilesByName(fileName).next()
    newFile.moveTo(parent)
    Logger.log('Created the file "%s" and moved to: "%s"', fileName, parentFolder)
    files = DriveApp.getFilesByName(fileName) //repeat else state makes things messy
  }

  var first = files.next();
  if (files.hasNext()) {
    Logger.log(first.getUrl())
    while (files.hasNext())
      Logger.log(files.next().getUrl())
    return new ReferenceError('Multiple Files found with: ' + fileName);
  }

  var sheetFile = SpreadsheetApp.openById(first.getId())
  var sheet = sheetFile.getSheetByName(sheetName)

  if (sheet == null) {
    Logger.log('Having to create "%s" Sheet in: "%s"', sheetName, sheetFile.getName())
    sheet = sheetFile.insertSheet(sheetName)
  } else {
    Logger.log('Detected "%s" Sheet in: "%s"' + (dontClear ? '' : ' and clearing it'), sheetName, sheetFile.getName())
    if (!dontClear)
      sheet.clearContents().clearFormats()
  }

  return sheet
}

function _sanitizeSheet(sheet, cols = false) {
  __removeEmptyColumns(sheet)
  __removeEmptyRows(sheet)
  sheet.autoResizeColumns(1, sheet.getLastColumn()) //todo - support reasonable multiline
  sheet.setFrozenRows(1)
  if (cols) sheet.setFrozenColumns(cols)
}

//FROM: https://stackoverflow.com/a/34781833

//Remove All Empty Columns in the Current Sheet
function __removeEmptyColumns(sheet) {
  var max = sheet.getMaxColumns(), last = sheet.getLastColumn()

  if (max - last != 0)
    sheet.deleteColumns(last + 1, max - last)

  sheet.autoResizeColumns(1, last)
}

//Remove All Empty Rows in the Current Sheet
function __removeEmptyRows(sheet) {
  var max = sheet.getMaxRows(), last = sheet.getLastRow()

  if (max - last != 0)
    sheet.deleteRows(last + 1, max - last)

  sheet.autoResizeRows(1, last)
}
