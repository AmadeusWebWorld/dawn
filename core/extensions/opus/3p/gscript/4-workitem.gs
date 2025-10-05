function TestWorkItemsReport() {
  const configObj = {
    'File': 'SKLS - Demo of Reporting', 'SheetOrTab': '**Work', UseDateSuffix: 'yes',
    //'Folder': 'TM WorkItems by MemberA', 'InSharedDrive': 'AW Opus - Automation - SKLS',
    'Folder': 'Demo Dept A', 'InSharedDrive': '',
    'Detection': '_by_description_', 'StatusTableName': 'SKLS WorkItem Information', 'LookupReminders' : '**Cal'
  }
  RefreshWorkItemsReport(configObj)
}

const _workItemsReportAliases = { Setting1: 'Folder', Setting2: 'InSharedDrive', Setting3: 'Detection', Setting4: 'StatusTableName', 'Setting5': 'LookupReminders' }

function RefreshWorkItemsReport(configObj) {
  const sheet = _getSheet(configObj.File, configObj.SheetOrTab + todayIfWanted(configObj.UseDateSuffix))
  Logger.log('About to run "%s" with %s', 'RefreshWorkItemsReport', JSON.stringify(configObj))

  sheet.clearContents()
  const headings = ['File', 'Status', 'Reminders', 'Description']
  sheet.appendRow(headings)
  extraStartsAt = headings.length + 1

  tableTitle = configObj.StatusTableName
  ourSheet = sheet

  const iterator = new OpusDrive(configObj.Folder, configObj.InSharedDrive)

  if (configObj.LookupReminders)
    remindersSheet = _getSheet(configObj.File, configObj.LookupReminders + todayIfWanted(configObj.UseDateSuffix), 'NONE', true)
  else
    remindersSheet = false //in case multiple calls

  appendResults(iterator.getFiles(onlyWorkitems)) //other filters tested..

  _sanitizeSheet(ourSheet, 3)
}

let tableTitle = 'DEMO Status', ourSheet, remindersSheet

function appendResults(files) {
  const sheet = ourSheet

  const remindersByFile = {}
  if (false && remindersSheet) {
    var cols = OpusColumns.invertHeadings(_calendarHeadings)
    for (let ix = 1; ix < remindersSheet.rows; ix++) {
      var row = remindersSheet.tableRows[ix]
      if (row[cols.FileId]) {
        const fileId = row[cols.FileId]
        if (!remindersByFile[fileId]) remindersByFile[fileId] = [];
        rtf = remindersSheet.tableRows
        remindersByFile[fileId].push(row[cols.FileId])
      }
    }
  }

  files.forEach(function (doc, fx) {

    const doc_r = __rtf(__cellRun(doc.name, doc.link, doc.opusType))

    let status = '_how-to-do'
    let reminders = '_todo'

    if (doc.opusType == 'doc') {
      const asDoc = Docs.Documents.get(doc.id)
      mergeFields(getTableAsFields(asDoc), sheet.getLastRow() + 1)
    }

    var vals = ['_adding', status, reminders, doc.description]
    const newRow = sheet.appendRow(vals)

    const index = sheet.getLastRow()
    newRow.getRange(index, 1).setRichTextValue(doc_r)
  })

  appendAllFields()
}

let extraStartsAt, allFields = {}, allObjects = []

function mergeFields(object, rowIndex) {
  if (object == false) {
    allObjects[rowIndex] = {}
    return
  }

  const keys = Object.keys(object)
  for (let ix = 0; ix < keys.length; ix++) {
    const key = keys[ix]
    const allX = allFields[key]
    //note - trim coz of empty rows in tables sometimes
    if (allX === undefined && key.trim() != '')
      allFields[key] = Object.keys(allFields).length + 1
  }

  allObjects[rowIndex] = object
}

//will work even if order is different
function appendAllFields() {
  const allKeys = Object.keys(allFields)

  const colsAt = ourSheet.getRange(1, extraStartsAt + 1, 1, allKeys.length)
  colsAt.setValues(eachItemAs1DArray(allKeys))

  //1 index as 0 is header row
  for (let hx = 2; hx <= allObjects.length; hx++) {
    const object = allObjects[hx]

    if (object) {
      const keys = Object.keys(object)
      const values = []

      for (let ix = 0; ix < allKeys.length; ix++) {
        const key = allKeys[ix]
        const allX = keys.indexOf(key)
        values[ix] = allX === -1 ? '' : object[key]
      }

      const cellsAt = ourSheet.getRange(hx, extraStartsAt + 1, 1, allKeys.length)
      cellsAt.setValues(eachItemAs1DArray(values))
    }
  }
}

function getTableAsFields(doc) {
  for (let ix = 0; ix < doc.body.content.length; ix++) {
    const table = doc.body.content[ix].getTable()
    if (table) {

      let titleMatches = tableTitle == ''
      if (!titleMatches) {
        const title = _contentAtEl0(table.tableRows[0])
        titleMatches = title.includes(tableTitle)
      }

      if (titleMatches) {
        const result = {}
        //expects a header row
        for (var rx = 1; rx < table.rows; rx++) {
          const row = table.tableRows[rx]
          result[_contentAtEl0(row)] = _contentAtEl0(row, 1)
        }
        return result
      }

    }
  }
  return false
}

function eachItemAs1DArray(values) {
  //https://stackoverflow.com/a/53905758
  result = [[]]
  for (let ix = 0; ix < values.length; ix++)
    result[0][ix] = [values[ix].trim()]

  return result
}

function _contentAtEl0(tableRow, cellIndex = 0) {
  return tableRow.tableCells[cellIndex].content[0].paragraph.elements[0].textRun.content
}
