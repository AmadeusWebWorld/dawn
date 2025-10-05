/*
  * [Opus] "Mage" is the Task Manager / Automation Dashboard
  * A part of Opus for Google by AmadeusWeb.com
  * Developed since 2025 and Copyrighted by Imran Ali Namazi
*/

const MageActions = {
  ExportCalendar: 'Export Calendar',
  WorkItemsReport: 'WorkItems Report',
}

let mageColumns

function RunMageForMe() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('Opus v2')

  if (sheet.getRange(1, 1, 1).getValue() != '#Action') {
    Logger.log('Fatal Error: Expecting active sheet to be special like our demo: \'Opus Mage Work\'')
    return
  }

  mageColumns = new OpusColumns(['Action', 'Skip', 'Organization', 'File', 'Access', 'SheetOrTab', 'UseDateSuffix', 'LastRun',
      'Setting1', 'Setting2', 'Setting3', 'Setting4', 'Setting5', 'Setting6'])
    /*
    .appendAliases({ Setting1: 'LabelFilter', Setting2: 'MainContactLabel', Setting3: 'ExtraFields' }, 'Pull Contacts')
    .appendAliases({ Setting1: 'OnlyOnLabel', Setting2: 'Fields' }, 'Contacts Fields')
    */
    .appendAliases(_exportCalendarAliases, MageActions.ExportCalendar)
    .appendAliases(_workItemsReportAliases, MageActions.WorkItemsReport)

  const rows = sheet.getRange(1, 1, sheet.getLastRow(), mageColumns.columnNames.length).getValues()

  rows.forEach(_runMageAction)
}

function _runMageAction(row) {
  if (row[0] == "" || row[0].substring(0, 1) == "#") return //empty or th

  const item = mageColumns.toObject(row)
  if (item.Skip == 'Y') return;

  //TODO: Access check - * for domain?

  Logger.log(JSON.stringify({ level: 'INFO', message: 'Found row for', rowIndex: row.xyz, organization: item.Organization, action: item.Action }))

  if (item.Action == 'Pull Contacts') {
    PullContactsInto(item)
  } else if (item.Action == MageActions.ExportCalendar) {
    FillCalendarItems(mageColumns.enrichObject(item, MageActions.ExportCalendar))
  } else if (item.Action == MageActions.WorkItemsReport) {
    RefreshWorkItemsReport(mageColumns.enrichObject(item, MageActions.WorkItemsReport))
  } else {
    Logger.log(JSON.stringify({ level: 'ERROR', message: 'Action: \'' + item.Action + '\' not defined / has typo' }))
  }
}
