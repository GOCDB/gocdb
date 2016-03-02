# Contributing to GOCDB

<a name="pull-requests"></a>
## Pull requests

We value good pull requests - patches, improvements and new features help alot! 

**Please ask first** before embarking on any significant pull request (e.g.
implementing features, refactoring code, porting to a different language),
otherwise you risk spending a lot of time working on something that the
project's developers might not want to merge into the project.

* Please feel free to contact EGI.eu and/or the gocdb-admins  at  mailman.eu maillist to discuss. 
* We request that developers fork the main repository into their own personal repository to work on features using Topic branches. 
* When ready, a pull request can be opened against the ‘dev’ branch in the main repository for review by other team members. 
* After review, the pull request may be merged into the ‘dev’ branch. 

### To open a pull request:

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

5. Locally merge (or rebase) the upstream development branch into your current topic branch (ie you have checked out the topic branch in step 3):

   ```bash
   git pull [--rebase] upstream dev
   ```

6. Push your topic branch up to your fork:

   ```bash
   # note, below does not setup <topic-branch-name> as a remote tracking branch 
   # so for future pushes you'll always need to name the remote
   git push origin <topic-branch-name>
   
   # to setup topic branch as remote tracking branch, use -u (or --set-upstream) 
   # which means you won't need to name the remote on future pushes 
   git push -u origin <topic-branch-name>
   ```

7. [Open a Pull Request](https://help.github.com/articles/using-pull-requests/)
    with a clear title and description.



