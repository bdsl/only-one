# Only One (work in progress - not currently ready for any use)

*(c) Barney Laurance 2022*

CLI tool to aquire a cooperative lock with a one-position queue, using
git as a distributed database.

The intention is to ensure that if a job can be trigged at any time only one instance
will actually run at any one time. A second job may be queued, if a third job is
required before the first one is finished then the second will be kicked out of the
queue and the third one takes its place.

Intended for uses such as running expensive or slow tests from build servers, or 
deployments.

This is inspired by the 
[concurrency feature in GitHub Actions](https://github.blog/changelog/2021-04-19-github-actions-limit-workflow-run-or-job-concurrency/), 
to allow use with other build/deploy systems.

## Usage:

(not yet fully implemented)

To acquire a lock on resource:

```shell
only-one start <resource> <repository>
```

If foo is currently available this will return zero immediately. If not it will register itself as waiting,
and wait for up to one hour polling for foo to become available. If there is a process already waiting it will kick
them out. If kicked it will return non-zero.

(Question - is there a need to return a unique random code?)

```shell
only-one release <resource> <repository>
```

Release the lock on `foo`. If there is a second process waiting to use `foo` then that will be started automatically.

## Advanced features to implement later:

- Forcefully kicking out currently held locks to deal with e.g. a crashed or misbehaving client.