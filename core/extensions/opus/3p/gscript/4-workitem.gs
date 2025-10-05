function TestWorkItemsReport() {
  const configObj = {
    'File': 'SKLS - Demo of Reporting', 'SheetOrTab': 'DemoWk', UseDateSuffix: 'yes',
    //'Folder': 'TM WorkItems by MemberA', 'InSharedDrive': 'AW Opus - Automation - SKLS',
    'Folder': 'Demo Dept A', 'InSharedDrive': '',
    'Detection': '_by_description_', 'StatusTableName': 'SKLS WorkItem Information', 'Setting5': 'LookupReminders'
  }
  RefreshWorkItemsReport(configObj)
}

const _workItemsReportAliases = { Setting1: 'Folder', Setting2: 'InSharedDrive', Setting3: 'Detection', Setting4: 'StatusTableName', 'Setting5': 'LookupReminders' }

//S. NO.	ASSIGNED DATE / QUERY DATE	WORK TYPE	ACTIVITY	COMPLETION STATUS	DEADLINE / INTERNAL TIMELINE	REMARKS

function RefreshWorkItemsReport(configObj) {
  const sheet = _getSheet(configObj.File, configObj.SheetOrTab + todayIfWanted(configObj.UseDateSuffix))
  Logger.log('About to run "%s" with %s', 'RefreshWorkItemsReport', JSON.stringify(configObj))

  sheet.clearContents()
  const headings = ['File', 'Status', 'Description', 'Reminders']
  sheet.appendRow(headings)
  extraStartsAt = headings.length

  tableTitle = configObj.StatusTableName
  ourSheet = sheet

  const iterator = new OpusDrive(configObj.Folder, configObj.InSharedDrive)

  if (configObj.LookupReminders && false)
    remindersSheet = _getSheet(configObj.File, configObj.LookupReminders + todayIfWanted(configObj.UseDateSuffix))

  appendResults(iterator.getFiles(onlyWorkitems)) //other filters tested..
}

let tableTitle = 'DEMO Status', ourSheet, remindersSheet

function appendResults(files) {
  const sheet = ourSheet

  files.forEach(function (doc, fx) {

    const doc_r = __rtf(__cellRun(doc.name, doc.link, doc.opusType))

    let status = '_todo - status' //from inside table
    let reminders = '_todo'; //from other sheet
    let fields = false

    if (doc.opusType == 'doc') {
      const asDoc = Docs.Documents.get(doc.id)
      fields = getTableAsFields(asDoc)
      if (fields) {
        Logger.log(JSON.stringify(fields))
      }
    }

    var vals = ['_adding', status, doc.description, reminders]
    const newRow = sheet.appendRow(vals)

    const index = sheet.getLastRow()
    newRow.getRange(index, 2).setRichTextValue(doc_r)
    mergeFields()

    //todo: append extra cols by checking against a configured master list setting
  })
}

let extraStartsAt, allFields = {}, allValues = {}

function mergeFields(object, rowIndex) {
  //todo
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

function _contentAtEl0(tableRow, cellIndex = 0) {
  return tableRow.tableCells[cellIndex].content[0].paragraph.elements[0].textRun.content
}
