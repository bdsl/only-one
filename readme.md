# Only One

*(c) Barney Laurance 2022*

CLI tool to aquire a cooperative lock with a one-position queue, using
git as a distributed database.

The intention is to ensure that if a job can be trigged at any time only one instance
will actually run at any one time. A second job may be queued, if a third job is
required before the first one is finished then the second will be kicked out of the
queue and the third one takes its place.

Intended for uses such as running expensive or slow tests from build servers, or 
deployments

