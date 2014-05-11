[![Omar G.](http://omarg.me/public/images/logo.png "Omar G. MyBB Page")](http://omarg.me/mybb "Omar G. MyBB Page")

## Newpoints Promotion System Integration
Allows administrators to set minimum newpoints points for the promotion system.

***

### Requirements
- [MyBB](http://www.mybb.com/downloads "Download MyBB") version 1.6.5+
- [Newpoints](http://mods.mybb.com/view/newpoints "Download Newpoints") plugin for MyBB to work.

### Installation
1. Upload the content of the "upload" folder to your MyBB root folder.
2. Go to ACP -> newpoints -> Plugins and activate/install the plugin.
3. Edit general settings from "ACP -> Newpoints -> Settings -> Promotion System Integration".
4. __Enjoy!__

### Update
##### 1.2 to 1.5
1. Deactivate old plugin (some plugin data will get lost).
2. Follow "Installation" instructions.
3. Go to acp -> Templates & Styles -> Templates -> Global Templates -> _npbumpthread_ and update its content to:
```html
<a href="{$threadlink}&amp;my_post_key={$mybb->post_code}" title="{$title}"><img src="{$theme['imglangdir']}/npbumpthread.gif" alt="{$title}" title="{$title}" /></a>&nbsp;
```

### Support
Please visit [MyBB Plugins](http://forums.mybb-plugins.com/Forum-Newpoints "Visit MyBB Plugins") for Newpoints support.

### Thank You!
Remember those are free releases developed on my personal free time let it be because I like it or because of customer's requests.

Thanks for downloading and using my plugins, I really appreciate it!