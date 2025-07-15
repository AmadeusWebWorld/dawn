/*
  * [Opus] "Mage" is the Task Manager / Automation Dashboard
  * A part of Opus for GW by AmadeusWeb.com
  * Developed since 2025 and Copyrighted by Imran Ali Namazi
*/

var mageActions, mageColumns

function RunMageForMe() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet()

  if (sheet.getRange(1, 1, 1).getValue() != '#Action') {
    Logger.log('Fatal Error: Expecting active sheet to be special like our demo: \'Opus Mage Work\'')
    return
  }

  mageActions = []
  mageColumns = _getColumns(['Action', 'Skip', 'File', 'Access', 'SheetOrTab', 'Setting1', 'Setting2', 'Setting3', 'LastRun'])
  _appendAliases(mageColumns, { Setting1: 'LabelFilter', Setting2: 'MainContactLabel', Setting3: 'ExtraFields' }, 'Pull Contacts')
  _appendAliases(mageColumns, { Setting1: 'OnlyOnLabel', Setting2: 'Fields' }, 'Contacts Fields')

  rows = sheet.getRange(1, 1, sheet.getLastRow() - 1, mageColumns.columnNames.length).getValues()

  rows.forEach(_runMageAction)
}

function _runMageAction(row) {
  if (row[0] == "" || row[0].substring(0, 1) == "#") return //empty or th

  const item = mageColumns.toObj(row)
  if (item.Skip == 'Y') return;

  //TODO: Access check - * for domain?

  Logger.log(item)

  if (item.Action == 'Pull Contacts') {
    _pullContactsInto(item)
  }
}
