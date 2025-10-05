function TestCalendarExport() {
  const configObj = {
    'File': 'SKLS - Demo of Reporting', 'SheetOrTab': 'All Calendar Events',
    'CalNameExclude': 'SKLS', 'TimeMin': '2025, 07, 01', 'TimeMax': '2026, 10, 01',
  }
  FillCalendarItems(configObj)
}

const _exportCalendarAliases = { Setting1: 'CalNameExclude', Setting2: 'TimeMin', Setting3: 'TimeMax' }

function FillCalendarItems(configObj) {
  const sheet = _getSheet(configObj.File, configObj.SheetOrTab)
  Logger.log('About to run "%s" with %s', 'FillCalendarItems', JSON.stringify(configObj))

  sheet.clearContents()

  sheet.appendRow(['CalendarName', 'EventName', 'EventDate', 'EventUrl', 'FileName', 'FileId', 'FileUrl'])

  const filter = { timeMin: _dateFromCsv(configObj.TimeMin), timeMax: _dateFromCsv(configObj.TimeMax) }
  const cals = Calendar.CalendarList.list()

  cals.items.forEach(function (cal, cx) {
    const calName = cal.getSummary() + ''

    if (configObj.CalNameExclude != '' && !calName.includes(configObj.CalNameExclude)) return;

    const events = Calendar.Events.list(cal.getId(), filter)
    events.items.forEach(function (event, ix) {

      let atts = event.getAttachments();
      if (!atts) atts = [{ title: '__no-file__', fileUrl: '#' }]

      //NOTE: let it be not normalized - 1 cal 1 doc only...
      atts.forEach(function (att, ax) {
        sheet.appendRow([calName, event.summary, event.start.date.toString(), event.htmlLink, att.title, att.fileId, att.fileUrl])
      })

    })
  })

  _sanitizeSheet(sheet)
}

function _dateFromCsv(txt) {
  const bits = (txt + '').split(', ')
  return new Date(bits[0], bits[1], bits[2]).toISOString()
}
