/*
  * [Opus] "Columns" is a helper file for using rows values as objects with properties
  * A part of Opus for Google by AmadeusWeb.com
  * Developed since 2025 and Copyrighted by Imran Ali Namazi
*/

function TestColumnAlias() {
  const testColumns = new OpusColumns(['Action', 'Skip', 'File', 'Access', 'SheetOrTab', 'Setting1', 'Setting2', 'Setting3', 'LastRun'])
    //tested and verified error when changing name to Setting2
    .appendAliases({ Setting1: 'LabelFilter', Setting2: 'MainContactLabel', Setting3: 'ExtraFields' }, 'Pull Contacts')
    //tested original fields are removed -- leave as 22 else throws error by design
    .appendAliases({ Setting1: 'OnlyOnLabel', Setting2: 'ExtraFields22' }, 'Contacts Fields')

  var testRow = ['one', 'two', 'tre', 'fou', 'fiv', 'six', 'sev', 'eit', 'nyn']
  var item = testColumns.toObject(testRow)
  Logger.log(JSON.stringify(item))

  //needs new object as orig fields are removed
  item = testColumns.toObject(testRow, 'Pull Contacts')
  Logger.log(JSON.stringify(item))

  //needs new object as orig fields are removed
  item = testColumns.toObject(testRow, 'Contacts Fields')
  Logger.log(JSON.stringify(item))
}

class OpusColumns {

  static invertHeadings(list, startAt = 1) {
    const result = {}
    for (let ix = 0; ix < list.length; ix++)
      result[startAt + ix] = list[ix]
    return result
  }

  constructor(names) {
    const cols = names.map(function (name, index) {
      return {
        name: name, index: index,

        getValue: function (arr) { return arr[this.index] },

        getRowValue: function (row) { return row.getRange(row.index, this.index).getValue() },
        setRowValue: function (row, value) { return row.getRange(row.index, this.index).setValue(value) },
        setRowRtfValue: function (row, rtf) { return row.getRange(row.index, this.index).setRichTextValue(row.index, rtf) },
      }
    })

    this.columnNames = names
    cols.forEach(function (col) { cols[col.name] = col }) //array and dict.. -> cannot read this until out of ctor
    this.columns = cols
  }

  appendAliases(aliases, aliasesGroupName) {
    if (this.aliasGroups == null) {
      this.aliasGroups = {}
    }

    const names = Object.keys(aliases)
    for (let ix = 0; ix < names.length; ix++) {
      const name = names[ix]
      const aliasName = aliases[name]

      if (this.columns[aliasName] != null) {
        Logger.log('Alias "' + name + '" already defined for Alias-set: ' + this.columns[name].aliasedAt)
        Logger.log(this.columns.aliases)
        throw new Error('Conflicting Alias')
      }

      if (this.columns[name] == null) {
        throw new Error('Column Not Found: ' + name)
      }

      this.columns[aliasName] = this.columns[name]
      this.columns[name].aliasedAt = aliasesGroupName
    }

    this.aliasGroups[aliasesGroupName] = aliases

    return this
  }

  toObject(arr, aliasesGroupName = false, removeOriginal = true) {
    var obj = {}

    //also cant use this.
    for (let ix = 0; ix < this.columnNames.length; ix++) {
      const col = this.columnNames[ix];
      obj[col] = arr[this.columns[col].index]
    }

    if (aliasesGroupName) {
      obj = this.enrichObject(obj, aliasesGroupName, removeOriginal)
    }

    return obj
  }

  ///NOTE: delete original to prevent use via both
  enrichObject(obj, aliasesGroupName = 'will throw error', removeOriginal = true) {
    const group = this.aliasGroups[aliasesGroupName]
    const aliases = Object.keys(group)

    for (let ix = 0; ix < aliases.length; ix++) {
      const aliasName = group[aliases[ix]]

      const originalName = this.columns[aliasName].name
      obj[aliasName] = obj[originalName]

      if (removeOriginal)
        delete obj[originalName]
    }

    return obj
  }
}
