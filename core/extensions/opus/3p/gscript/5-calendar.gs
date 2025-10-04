function fillCalendarItems(sheet) {
  sheet.clearContents();

  sheet.appendRow(['calName', 'eventName', 'eventDate', 'eventUrl', 'docName', 'docUrl'])

  const filter = { timeMin: new Date(2025, 07, 01).toISOString(), timeMax: new Date(2026, 10, 01).toISOString() }
  const cals = Calendar.CalendarList.list()

  cals.items.forEach(function (cal, cx) {
    const calName = cal.getSummary() + ''

    if (!calName.includes('SKLS')) return;

    const events = Calendar.Events.list(cal.getId(), filter)
    events.items.forEach(function (event, ix) {

      let atts = event.getAttachments();
      if (!atts) atts = [{ title: '__no-file__', fileUrl: '#' }]

      //NOTE: let it normalize - 1 cal 1 doc only...
      atts.forEach(function (att, ax) {
        sheet.appendRow([calName, event.summary, event.start.date.toString(), event.htmlLink, att.title, att.fileUrl])
      })

    })
  })
}
