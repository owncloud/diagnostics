# ownCloud Diagnostics
:hospital:

[![Build Status](https://drone.owncloud.com/api/badges/owncloud/diagnostics/status.svg?branch=master)](https://drone.owncloud.com/owncloud/diagnostics)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_diagnostics&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_diagnostics)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_diagnostics&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_diagnostics)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_diagnostics&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_diagnostics)

- [x] Support for 10.0
- [x] Support for 9.1 - [on branch stable9](https://github.com/owncloud/diagnostics/tree/stable9)
- [x] Support for 9.0 - [on branch stable9.1](https://github.com/owncloud/diagnostics/tree/stable9.1)

**Versions for ownCloud 9.0 and 9.1 are limited and only work in debug mode and without user interface. For more features, please update your server version to 10.0**

Enabling this ownCloud diagnostic module will result in collecting data about all queries and events in the system per request.

It will collect information about any type of the request to the server, summarize it and store in the form of log or in any other requested form (export to monitoring).

Module allows to diagnose only selected users after their authentication or enabling collecting data globally for all users with "debug mode".

You can collect data from selected users not affecting performance of the system of other users (data wont be collected and logged).

**By default, none of users is selected - to start logging please select user or allow collecting all data**

![Demo Screen](/img/demo1.jpg?raw=true "OwnCloud Diagnostics")

![Demo Screen](/img/demo2.jpg?raw=true "OwnCloud Diagnostics")

# Default log location

Log can be found in the folder `data/diagnostic.log`

# Installation

To install, go to ```/apps``` in your ownCloud installation directory and ```git clone https://github.com/owncloud/diagnostics```. In the apps admin panel enable Diagnostics app.

- To enable app using command line:

`sudo -u www-data php occ app:enable diagnostics`


- To enable [log levels](/lib/Diagnostics.php), e.g. "SUMMARY":

`sudo -u www-data php occ config:app:set --value 1 diagnostics diagnosticLogLevel`


- To enable logging after authentication of specific users using command line:

`sudo -u www-data php occ config:app:set --value "[\"test_shareMountInit\", \"admin\"]" diagnostics diagnosedUsers`


- To enable debug mode globally:

`sudo -u www-data php occ config:system:set --value true debug`

# Usage

Each SUMMARY log if referencing EVENT and QUERY logs via `reqId`.
This allows to build full timeline of the events and corresponding queries for specific type of request e.g. "PROPFIND", only for selected users, as requests are coming.

**Exemplary query log:**

```
{
    "type":"QUERY",
    "reqId":"JaOvGavLZar0Idhpq5AE",
    "diagnostics":{
        "sqlStatement":"SELECT s.*, f.`fileid`, f.`path`, st.`id` AS `storage_string_id` FROM `oc_share` s LEFT JOIN `oc_filecache` f ON s.`file_source` = f.`fileid` LEFT JOIN `oc_storages` st ON f.`storage` = st.`numeric_id` WHERE (`share_type` = :dcValue1) AND (`share_with` IN (:dcValue2)) AND ((`item_type` = :dcValue3) OR (`item_type` = :dcValue4)) ORDER BY s.`id` ASC LIMIT 18446744073709551615 OFFSET 0",
        "sqlParams":"array (   'dcValue1' => 1,   'dcValue2' =>    array (     'admin' => 'admin',   ),   'dcValue3' => 'file',   'dcValue4' => 'folder', )",
        "sqlQueryDurationmsec":0.22697448730469,
        "sqlTimestamp":1492690459.1778
    }
}
```

**Exemplary event log:**
```
{
    "type":"EVENT",
    "reqId":"JaOvGavLZar0Idhpq5AE",
    "diagnostics": {
        "eventDescription":"Setup filesystem",
        "eventDurationmsec":5.1379203796387,
        "eventTimestamp":1492690459.1743
    }
}
```

**Exemplary summary log:**

```
{
    "type":"SUMMARY",
    "reqId":"x0GaTSDAElJ0lKtCj0Wb",
    "time":"2017-04-20T12:14:20+00:00",
    "remoteAddr":"127.0.0.1",
    "user":"admin",
    "method":"PROPFIND",
    "url":"\/owncloudtest\/remote.php\/webdav\/",
    "diagnostics":{
        "totalSQLQueries":19,
        "totalSQLDurationmsec":2.1142959594727,
        "totalSQLParams":45,
        "totalEvents":9,
        "totalEventsDurationmsec":5.9356689453125
    }
}
```


