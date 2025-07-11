<<<<<<< HEAD
<?php
function hasClinicAccess($requested_clinic_id) {
    if (!isset($_SESSION['clinic_id'])) {
        return false;
    }

    // Admin users have access to all clinics
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        return true;
    }

    // Regular users can only access their assigned clinic
    return $_SESSION['clinic_id'] == $requested_clinic_id;
}

function getAccessibleClinics() {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        // Admin gets all clinics
        $sql = "SELECT clinic_id, name FROM facility";
    } else {
        // Regular users get only their clinic
        $sql = "SELECT clinic_id, name FROM facility WHERE clinic_id = ?";
        $params = [$_SESSION['clinic_id']];
    }

    // Execute query and return results
    // ... implementation details ...
}

function filterPatientsByClinic($query) {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        $query .= " AND clinic_id = " . intval($_SESSION['clinic_id']);
    }
    return $query;
}![Syntax Status](https://github.com/openemr/openemr/workflows/Syntax/badge.svg?branch=rel-703)
![Styling Status](https://github.com/openemr/openemr/workflows/Styling/badge.svg?branch=rel-703)
![Testing Status](https://github.com/openemr/openemr/workflows/Test/badge.svg?branch=rel-703)

[![Backers on Open Collective](https://opencollective.com/openemr/backers/badge.svg)](#backers) [![Sponsors on Open Collective](https://opencollective.com/openemr/sponsors/badge.svg)](#sponsors)

# OpenEMR

[OpenEMR](https://open-emr.org) is a Free and Open Source electronic health records and medical practice management application. It features fully integrated electronic health records, practice management, scheduling, electronic billing, internationalization, free support, a vibrant community, and a whole lot more. It runs on Windows, Linux, Mac OS X, and many other platforms.

### Contributing

OpenEMR is a leader in healthcare open source software and comprises a large and diverse community of software developers, medical providers and educators with a very healthy mix of both volunteers and professionals. [Join us and learn how to start contributing today!](https://open-emr.org/wiki/index.php/FAQ#How_do_I_begin_to_volunteer_for_the_OpenEMR_project.3F)

> Already comfortable with git? Check out [CONTRIBUTING.md](CONTRIBUTING.md) for quick setup instructions and requirements for contributing to OpenEMR by resolving a bug or adding an awesome feature 😊.

### Support

Community and Professional support can be found [here](https://open-emr.org/wiki/index.php/OpenEMR_Support_Guide).

Extensive documentation and forums can be found on the [OpenEMR website](https://open-emr.org) that can help you to become more familiar about the project 📖.

### Reporting Issues and Bugs

Report these on the [Issue Tracker](https://github.com/openemr/openemr/issues). If you are unsure if it is an issue/bug, then always feel free to use the [Forum](https://community.open-emr.org/) and [Chat](https://www.open-emr.org/chat/) to discuss about the issue 🪲.

### Reporting Security Vulnerabilities

Check out [SECURITY.md](.github/SECURITY.md)

### API

Check out [API_README.md](API_README.md)

### Docker

Check out [DOCKER_README.md](DOCKER_README.md)

### FHIR

Check out [FHIR_README.md](FHIR_README.md)

### For Developers

If using OpenEMR directly from the code repository, then the following commands will build OpenEMR (Node.js version 20.* is required) :

```shell
composer install --no-dev
npm install
npm run build
composer dump-autoload -o
```

### Contributors

This project exists thanks to all the people who have contributed. [[Contribute]](CONTRIBUTING.md).
<a href="https://github.com/openemr/openemr/graphs/contributors"><img src="https://opencollective.com/openemr/contributors.svg?width=890" /></a>


### Sponsors

Thanks to our [ONC Certification Major Sponsors](https://www.open-emr.org/wiki/index.php/OpenEMR_Certification_Stage_III_Meaningful_Use#Major_sponsors)!


### License

[GNU GPL](LICENSE)
=======
# openemer-weightloss
openemer-weightloss
>>>>>>> a29f46c2aeb7fde53a944e533e5443fd56c47452
