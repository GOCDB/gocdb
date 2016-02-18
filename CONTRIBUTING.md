# Contributing to GOCDB

<a name="pull-requests"></a>
## Pull requests

We value good pull requests - patches, improvements and new features help alot! 

**Please ask first** before embarking on any significant pull request (e.g.
implementing features, refactoring code, porting to a different language),
otherwise you risk spending a lot of time working on something that the
project's developers might not want to merge into the project.

* Please feel free to contact EGI.eu and/or the gocdb-admins  at  mailman.eu maillist. 


To open a pull request
1. [Fork](https://help.github.com/articles/fork-a-repo/) the project, clone your
   fork, and configure the remotes:

   ```bash
   # Clone your fork of the repo into the current directory
   git clone https://github.com/<your-username>/gocdb.git
   # Navigate to the newly cloned directory
   cd gocdb
   # Assign the original goc repo to a remote called "upstream"
   git remote add upstream https://github.com/GOCDB/gocdb.git
   ```

2. If time has passed since you cloned, get the latest changes from upstream:

   ```bash
   git checkout master
   git pull upstream master
   git checkout dev 
   git pull upstream dev 
   ```

3. Create a new topic branch (off the main dev branch) to
   contain your feature, change, or fix:

   ```bash
   git checkout dev
   git checkout -b <topic-branch-name>
   ```

4. Commit your changes in logical chunks. Please adhere to these [git commit
   message guidelines](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html). 
   Use Git's
   [interactive rebase](https://help.github.com/articles/about-git-rebase/)
   feature to tidy up your commits before making them public.

5. Locally merge (or rebase) the upstream development branch into your topic branch:

   ```bash
   git pull [--rebase] upstream dev
   ```

6. Push your topic branch up to your fork:

   ```bash
   git push origin <topic-branch-name>
   ```

7. [Open a Pull Request](https://help.github.com/articles/using-pull-requests/)
    with a clear title and description.



