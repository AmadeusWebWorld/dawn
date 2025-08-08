### Read and agree to the

 * Terms
 * Spirit
 * Notices

---

### Install and get localhost running

DIV|LARGELIST

1. Setup Folder & Programs
	1. Create Folder: `D:\AmadeusWeb\` and download these installer files to ./Setups
	1. Install [Git](https://git-scm.com/download/win), [TortoiseGit](https://tortoisegit.org/download/), [Apache](https://www.apachefriends.org/) <sup>1</sup> and [VSCode](https://code.visualstudio.com/download).
	1. Additionally, [BeyComp](https://www.scootersoftware.com/download.php) or [PSPad](https://pspad.com/en/) may come in handy.
	1. For uploading (delta only) to FTP, I've been [using this](%url%utilities/#ftp-uploader) which I wrote in 2007.
2. <sup>1</sup> Install and Configure Server
	1. Install Xampp (Windows + Apache + PHP) to `D:\AmadeusWeb\xampp` - MySql not needed for AmadeusWeb at all.
	1. Run "xampp-control.exe" as admin and change the httpd.conf file
	1. Change the DocumentRoot from `D:\AmadeusWeb\xampp\htdocs` to `D:\AmadeusWeb\dawn`
	1. Tick the checkbox to allow Apache to be installed as a service. Also click run (row should hae a green lael saying running on port 80).
	1. Visit [localhost](http://localhost/amadeus8) in the browser - it should show the amadeus8 site content but broken <sup>2</sup>.
3. <sup>2</sup> Extract Theme Assets
	1. Navigate to `D:\AmadeusWeb\dawn\amadeus8\themes\canvas\`
	1. Run `install-theme.bat` - do this everytime the theme assets are changed, renaming the old assets folder (in case it has pending changes etc).
	1. Visit localhost again, it should be working (network tab shows all assets loaded).
	1. Click the button 'Enter Core v8 Site` - you should see the documentation.

DIV-CLOSE

> In 2024, we had [v6.5.x](https://builder.amadeusweb.com/how-to/setup-on-your-machine/).<br />
> We also suggested Chocolatey for some time back then.

---

## Installation on Mobile (Android)

DIV|LARGELIST

1. Git Client - [PocketGit](https://drive.google.com/file/d/1L2yCUxSk-M3Lpy0KLyp72ZKrDJMdEJJ2/view?usp=drive_link). Dawn > Admin > [Git Manager](http://localhost/amadeus8/git-manager/) is still only a hope for linux / mobile, but repos are listed.
2. AWebServer - [install](https://play.google.com/store/apps/details?id=com.sylkat.apache) - no url rewriting, but this is taken care of. [Document Root](https://builder.amadeusweb.com/assets/docs/mobile-web-server.jpg) MUST be /sdcard/Magique. Port can be 80 or 8080 - its fine.
3. Theme assets automatically loaded from hosting, not needed to extract zip on phone.

DIV-CLOSE
