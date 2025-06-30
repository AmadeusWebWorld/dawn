function __cellRun(text, link, type) {
  return { text: text + ' (' + type + ')', link }
}

function __rtf(item) {
  return SpreadsheetApp.newRichTextValue().setText(item.text).setLinkUrl(item.link).build()
}

function __concatenateRuns(items) {
  if (items.length == 0) return []
  if (items.length == 1) return __rtf(items[0])

  const colors = ['red', 'green', 'blue', 'brown', 'gray']

  const slash = " / \r"

  let lastBreak = 0
  const runs = items.map(function (itm, ix) {
    const thisResult = {
      text: itm.text,
      suffix: ix < items.length - 1 ? slash : '',
      color: colors[ix],
      link: itm.link,
      start: lastBreak,
      length: itm.text.length,
    }

    lastBreak += thisResult.text.length + thisResult.suffix.length
    return thisResult
  })

  const result = SpreadsheetApp.newRichTextValue().setText(runs.map(function (r) { return r.text + r.suffix }).join(''))

  runs.forEach(function (bit) {
    const color = SpreadsheetApp.newTextStyle().setBold(true).setForegroundColor(bit.color).build()
    result.setLinkUrl(bit.start, bit.start + bit.text.length, bit.link).setTextStyle(bit.start, bit.start + bit.length, color)
  })

  return result.build();
}
