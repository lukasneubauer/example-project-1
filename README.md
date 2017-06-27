# API

The goal of this project is to provide api service.

## Configuration and Setup

To configure the environment, run:

```
bin/docker_configure
```

Answer: `1`, `Y` and `Y` to configure the environment for development.

Answer: `3`, `N` and `N` to configure the environment for production.

Answer: `5`, `Y` and `N` to configure the environment for testing.

After configuration, run:

```
bin/docker_build
```

to build Docker containers and after the build is done, run:

```
bin/docker_up
```

to start Docker containers and then, run:

```
bin/docker_initialize
```

to initialize the environment.

Finally, run:

```
bin/docker_down
```

to stop Docker containers.

## Data

To initialize database, run:

```
bin/make init
```

To initialize database with data for development, run:

```
bin/make init-full
```

To initialize database with data for testing (phpunit), run:

```
bin/make init-full-for-phpunit
```

To initialize database with data for testing (dredd), run:

```
bin/make init-full-for-dredd
```

## Testing

To configure the environment for testing, answer `5`, `Y` and `N` when running:

```
bin/docker_configure
```

Then run the series of following commands:

```
bin/docker_build
```

```
bin/docker_up
```

```
bin/docker_initialize
```

### PHPUnit

Run series of following commands to run tests for phpunit:

```
bin/make init-full-for-phpunit
```

```
bin/phpunit
```

### Dredd

Run series of following commands to run tests for dredd:

```
bin/make init-full-for-dredd
```

```
bin/dredd
```

## Users In Development Environment

| Email                | Password | Account           |
|----------------------|----------|-------------------|
| john.doe@example.com | secret   | Teacher           |
| jane.doe@example.com | secret   | Student           |
| jake.doe@example.com | secret   | Teacher & Student |
