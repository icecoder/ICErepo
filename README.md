ICErepo
=======

Show diffs, push, pull &amp; sync your site and Github repo's.

While Github has a fantastic website, mobile app, desktop app and of course bash system, there's no web based UI I can find to sync your website code with Github repo's or vice versa. That's what ICErepo provides.

Originally intended to be a plugin for ICEcoder (https://github.com/mattpass/ICEcoder), I have decided to make it a standalone lib so it can run by itself or easily be integrated into any existing system.

The aim is a simple UI to view diffs between your server dir's and related Github repo's. This list will consist of new files (those only on server), deleted files (those only on Github) and changed files (files that exist in both places but are different). Files that exist in both locations and the same are not shown to keep things minimalist.

Users can then to pick & choose the files they'd like to commit, provide a title and message, then commit to Github. As each file is synced by the user to match the server it dissapears from the UI list. Alternatively you can pull files & folders from Github to sync your server dir's with the repo itself.

Cool huh?

**Current screnshot:**

<img src="http://www.mattpass.com/images/icerepo.png" alt="ICErepo screenshot">

This lib uses customised & minified versions of these brilliant and time tested repos:

Github API lib: https://github.com/michael/github

JS Diff lib:    https://github.com/cemerick/jsdifflib

###Installation

####Step 1: Clone the repo

```
$ git clone git://github.com/mattpass/ICErepo
```

####Step 2: Enter your auth settings
```
Open settings.php and enter either your Github oauth token or username & password
oauth is the better choice of the two here, view http://developer.github.com/v3/oauth/ for info
(If using oauth ensure you have repo scope & your app is granted the URL you'll run under). You can also omit entering any details here and it will ask for either of these details to use in the session. A token set in the session is the recommended approach here.
```

####Step 3: Enter your repo & server dir settings
```
Also in settings.php, enter the repo & corresponding server paths
Enter 'selected' as a 3rd param next to your default repo/server option to autoload that
Finally, set $_SESSION['userLevel'] to be > 0 with your own login system
Upload ICErepo, visit in a web browser & enjoy
```