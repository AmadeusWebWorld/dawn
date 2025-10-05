/*
  * [Opus] "Drive" is a helper file for traversing google drive
  * A part of Opus for Google by AmadeusWeb.com
  * Developed since 2025 and Copyrighted by Imran Ali Namazi
*/

function TestOpusDrive() {
  const r = new OpusDrive('Demo Dept A', '').getFiles(onlyWorkitems)
  Logger.log(r)
}

const onlyWorkitems = 'docs description d-is-workitem'
const onlyDocs = 'docs description'
const onlyDocsNoDescription = 'docs'

class OpusDrive {
  constructor(name, sharedDrive) {
    this.isSharedDrive = false //sharedDrive != ''

    if (this.isSharedDrive)
      this.topDrive = this._getSharedDrive(sharedDrive)
    else
      this.topDrive = false

    this.topFolder = this._getFolder(name)
  }

  getFolders() {

  }

  getFiles(filter, folder = null) {
    //match criteria
    const m = {
      docs: filter.includes('docs'),
      desc: filter.includes('description'),
      diwo: filter.includes('d-is-workitem'),
    }

    const files = this._getFilesOf(folder)
    const result = []
    files.forEach(function(fil, fx){

      let type = 'file', mimeType = fil.getMimeType()

      if (mimeType == 'application/vnd.google-apps.document' || mimeType == 'docx')
        type = 'doc'
      else if (mimeType == '' || mimeType == 'xlsx')
        type = 'sheet'
      else if (mimeType == '')
        type = 'pdf'
      
      const desc = fil.getDescription()

      if (m.docs && type != 'doc')
        return
      if (m.desc && (desc == null || desc == ''))
        return
      if (m.diwo && (desc == null || !desc.includes('is-workitem')))
        return
      
      var item = {
        id: fil.getId(),
        name: fil.getName(),
        link: fil.getUrl(),
        description: desc,
        opusType: type,
        mimeType: mimeType,
      }

      result.push(item)
    })

    return result
  }

  _getFilesOf(folder = null) {
    if (!folder) folder = this.topFolder
    var files = folder.getFiles()

    const result = []
    while (files.hasNext())
      result.push(files.next())

    result.sort(_sortAscending)
    return result;
  }

  _getFolder(name) {
    if (!this.topDrive)
      return DriveApp.getFoldersByName(name).next();

    //    
    //  return DriveApp.getFolderById(this.topDrive.id)
    //else
    
  }

  _getSharedDrive(name) {
    return Drive.Drives.list({ q: 'name = "' + name + '"', supportsAllDrives: true }).drives.pop()
  }
}
