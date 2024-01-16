# GoDaddy Launch

- [GoDaddy Launch](#godaddy-launch)
	- [Development](#development)
	- [Resetting state to default](#resetting-state-to-default)
		- [Cleaning up commits](#cleaning-up-commits)
	- [How to build a release zip for testing](#how-to-build-a-release-zip-for-testing)
	- [Deploy latest to the System Plugin](#deploy-latest-to-the-system-plugin)


## Development

Install the necessary Node.js and Composer dependencies:
```
composer install && npm install
```

## Resetting state to default

Run the following command:

`npm run reset`

### Cleaning up commits

Beacuse of how the deploy to the system plugin works, we need to commit compiled and the composer autoloader. Depending on the development going on there are a lot of files that we want to purge. Files like CSS .map files and development only composer dependencies.

Run the following command to clean up those files and commit the changes:
```
npm run clean:commit && git commit -am "cleanup commit"
```

## How to build a release zip for testing

While inside the repository directory run the following.
```
composer run build-release
```

The release zip will be found in the parent directory with the plugin slug and version number.

## Deploy latest to the System Plugin

When we are ready for a release we need to tag a new version to release and then update the system plugin reference with the latest tag. Determine the next release tag using [semantic versionaing](https://semver.org/) and run the following script.

`composer run prepare-release X.X.X`

Finally, create a PR in the [gdcorp-wordpress/wp-paas-system-plugin](https://github.com/gdcorp-wordpress/wp-paas-system-plugin) repository and assign to @mrebernisak. [Example](https://github.com/gdcorp-wordpress/wp-paas-system-plugin/pull/10). The release script will output a link to the branch diff which you can create a PR from.

---
*The `build` directory must be included in version control for the system plugin deployment. This is not ideal but is currently the way it must be until the system plugin deployment process is updated.*
