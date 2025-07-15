/*
  * [Contacts] "Engage" Oversees Outreach and Interaction with a real life / global community
  * A part of Opus for GW by AmadeusWeb.com
  * Developed since 2025 and Copyrighted by Imran Ali Namazi
  * TODO:
    * Include Notes and Custom Fields
    * Sheets of Multiple Contacts - Given in Input
  * TRAINING
    * Wean away from sending files to sending links / referredBy links
*/

function NoopEngage() { }

function _pullContactsInto(item) {
  const sheet = _getSheet(item.File, item.SheetOrTab)
  Logger.log('About to run "%s" with %s', '_pullContactsInto', JSON.stringify(item))

  sheet.appendRow([
    "#GWID",
    "Name",
    "Labels",
    "Number",
    "Email",
    "Link",
    "WhatsApp",
  ])

  const people = __getContacts()
  //console.log('People: %s', JSON.stringify(people, null, 2))

  people.sort(function (a, b) { return a.name == b.name ? 0 : (a.name < b.name ? -1 : 1) })
  const names = people.map(function (p) { return p.name }).join(', ')

  people.forEach(function (person, ix) {
    const newRow = sheet.appendRow([
      person.gwid,
      person.name,
      person.labels,
      '.', //person.email,
      '.', //person.phone,
      '.', //person.whatsapp,
      person.link,
    ])

    const index = ix + 2
    newRow.getRange(index, 4).setRichTextValue(person.phone)
    newRow.getRange(index, 5).setRichTextValue(person.email)
    newRow.getRange(index, 6).setRichTextValue(person.whatsapp)
  })

  _sanitizeSheet(sheet)

  Logger.log('Names Found: %s', names)
  Logger.log('Wrote %s Contacts to "%s" Sheet of "%s" File', people.length, item.SheetOrTab, item.File)
}

function __getContacts() {
  const labelsByMember = __getLabelsByMember()

  const connections = People.People.Connections.list('people/me', { personFields: 'names,phoneNumbers,emailAddresses' }).connections

  let people = [], tel = '\'' /* TODO: tel: */, mailto = 'mailto:', wame = 'https://wa.me/'
  const warning = SpreadsheetApp.newTextStyle().setBold(true).setForegroundColor('maroon').build()
  const none = SpreadsheetApp.newRichTextValue().setText('none').setTextStyle(warning).build()

  connections.forEach(function (contact) {
    const labels = labelsByMember[contact.resourceName] ? labelsByMember[contact.resourceName].join(', ') : ''

    const numbers = contact.phoneNumbers == undefined ? none :__concatenateRuns(contact.phoneNumbers.map(function (no) { return __cellRun(no.canonicalForm, tel + no.canonicalForm, no.formattedType) }))
    const whatsapps = contact.phoneNumbers == undefined ? none : __concatenateRuns(contact.phoneNumbers.map(function (no) { return __cellRun(no.canonicalForm, wame + no.canonicalForm.replace('+', ''), no.formattedType) }))
    const emails = contact.emailAddresses == undefined ? none : __concatenateRuns(contact.emailAddresses.map(function (em) { return __cellRun(em.value, mailto + em.value, em.formattedType) }))

    const link = 'https://contacts.google.com/person/' + contact.resourceName.replace('people/', '')

    if (contact.names == null) {
      Logger.log('No Name for: ' + link)
      return
    }

    const person = {
      'gwid': contact.resourceName,
      'name': contact.names[0].displayName,
      'labels': labels,
      'email': emails,
      'phone': numbers,
      'whatsapp': whatsapps,
      'link': link,
    }

    people.push(person)
    //console.log('Person: %s', JSON.stringify(person, null, 2))
  })

  return people
}

function __getLabelsByMember() {
  const labels = People.ContactGroups.list({}).contactGroups.filter(function (gr) { return gr.groupType != "SYSTEM_CONTACT_GROUP" })
  //console.log('All Labels: %s', JSON.stringify(contactGroups, null, 2))

  let labelsByMember = {}
  labels.forEach(function (grp) {
    const members = People.ContactGroups.get(grp.resourceName, { maxMembers: 10000 }).memberResourceNames
    if (members == null) return;

    //console.log('All Members of %s: %s', grp.formattedName, JSON.stringify(members, null, 2))

    members.forEach(function (mem) {
      var labels = labelsByMember[mem] ? labelsByMember[mem] : [];
      labels.push(grp.formattedName)
      labelsByMember[mem] = labels
    })
  })

  //console.log('Labels of Members of: %s', JSON.stringify(labelsByMember, null, 2))
  return labelsByMember
}