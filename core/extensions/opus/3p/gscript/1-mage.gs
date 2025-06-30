/*
  * [Opus] "Mage" is the Task Manager / Automation Dashboard
  * A part of Opus for GW by AmadeusWeb.com
  * Developed since 2025 and Copyrighted by Imran Ali Namazi
*/

var mageActions

function RunMageForMe() {
  const sheetName = 'Opus Mage Work'

  mageActions = []

  rows = sheet.getRange(1, 1, sheet.getLastRow() - 1, 6).getValues()

  rows.forEach(_addMageAction)
  sheetFile.setActiveSheet(sheet)

  mageActions.forEach(_runMageAction)
}

//#Action	Skip	Where	SettingA	SettingB	SettingC	Output
function _addMageAction(item) {
  if (item[0] == "" || item[0].substring(0, 1) == "#") return //empty or th
  if (item[1] == "Y") return //skip

  mageActions.push({
    name: item[0],
    skip: item[1],
    wher: item[2],
    varA: item[3],
    varB: item[4],
    varC: item[5],
    indx: actions.length + 1
  })
}

function _runMageAction(itm) {
  if (itm.name == 'Pull Contacts') {
    _pullContactsInto(itm.varA, itm.varB, itm.varC)
  }
}
