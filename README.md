# Metadata Hunter

[![Docker Repository on Quay](https://quay.io/repository/keboola/metadata-hunter/status "Docker Repository on Quay")](https://quay.io/repository/keboola/metadata-hunter)
[![Build Status](https://travis-ci.org/keboola/google-analytics-extractor.svg?branch=master)](https://travis-ci.org/keboola/metadata-hunter)
[![Code Climate](https://codeclimate.com/github/keboola/google-analytics-extractor/badges/gpa.svg)](https://codeclimate.com/github/keboola/metadata-hunter)
[![Test Coverage](https://codeclimate.com/github/keboola/google-analytics-extractor/badges/coverage.svg)](https://codeclimate.com/github/keboola/metadata-hunter/coverage)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/keboola/metadata-hunter/blob/master/LICENSE.md)

Docker application for gathering system metadata.

## Example configuration

```yaml
parameters:
  targets:
    -
        componentId: transformation
```

### Tests

Run the tests: `./tests.sh`



