
# GitHub Repository Creator

Creates a GitHub repository and initializes it.

You can create a repository for a new project:
- with a description
- public or private
- with a default branch
- with files by default
- with labels for PRs and issues
- with contributors to invite
- with milestones
- with CODEOWNERS
- with CI pre-configured

You can configure lots of options using a yaml file.
To initialize the configuration file, run the command **init**.

On failure, by default, the newly created repository will be deleted. You can disable this behavior using the **--keep-on-failure** option. A delay also exists to abort.

## Installation

Releases are located at https://github.com/atournayre/repository-creator/releases/latest

Download the latest phar.

## Build a phar
1. Install https://github.com/clue/phar-composer
2. Run `make build-phar`

## Usage

### To list all the commands available
```shell
php respository-creator.phar list
```
### To initialize the configuration

This will create a file named **create_repository.yaml** and download issues templates and files templates in the same directory as the configuration file.

It is possible to specify the repository for issues templates or files templates.

Once files downloaded, you can edit them or add some and reference them in the configuration file.
```shell
php respository-creator.phar init create_repository.yaml
```
### To create a repository
```shell
php respository-creator.phar create create_repository.yaml
```


## GitHub Token Authorizations

Got to https://github.com/settings/tokens

Then create a "Fine-grained token" with the following authorizations:

In section "Repository permissions"

| Section        | Permission     |
|----------------|----------------|
| Actions        | Read and write |
| Administration | Read and write |
| Contents       | Read and write |
| Issues         | Read and write |
| Metadata       | Read only      |
| Workflows      | Read and write |

## Configuration

Here is an example of the configuration file

/!\ Template section is present but actually not working!

```yaml
create_repository:
  # Locale
  locale: en
  # Authentication
  github_token: ~
  # User
  user: ~
  # Defaults
  defaults:
    client_name: default-client
    project_name: default-project
    project_type: application
    description: This is a default project
    visibility: private
    main_branch: main
    contributors:
      - user1
      - user2
  # Repository
  project_types:
    - component
    - bundle
    - application
    - template
    - issues-tracker
    - poc
  # Branches
  main_branch: ~
  develop_branch: develop
  branches:
    - main
    - develop
  # Labels
  labels:
    - {name: 'docker', color: 'FF5733', description: 'A Docker file has been added or modified'}
    - {name: 'dependencies', color: 'EC7063', description: 'A composer or package file has been added or modified'}
    - {name: 'fixtures', color: '85C1E9', description: 'Fixtures have been added or modified'}
    - {name: 'tests', color: '7DCEA0', description: 'Tests have been added or modified'}
    - {name: 'tools', color: '2E86C1', description: 'Tools have been added or modified'}
    - {name: 'deployment', color: '884EA0', description: 'Deployment files have been added or modified'}
    - {name: 'docs', color: 'F5B041', description: 'Documentation has been added or modified'}
    - {name: 'ci', color: 'D35400', description: 'CI files have been added or modified'}
    - {name: 'migrations', color: 'BA4A00', description: 'Database migrations have been added or modified'}
    - {name: 'front', color: '7DCEA0', description: 'Related to the front'}
    - {name: 'api', color: 'F1948A', description: 'Related to the API'}
    - {name: 'back', color: 'F7DC6F', description: 'Related to the back'}
    - {name: 'env', color: '117A65', description: 'Related to .env files'}
    - {name: 'git', color: '633974', description: 'Related to git files'}
    - {name: 'pull request', color: '16A085', description: 'A PR has been opened'}
    - {name: 'to test (internal)', color: 'D98880', description: 'To be tested (internal)'}
    - {name: 'to test (customer)', color: 'F1C40F', description: 'To be tested (customer)'}
  # Template
  #enable_no_template: true
  #templates:
    # To create a new repository in an organization, the authenticated user must be a member of the specified organization.
    # https://docs.github.com/fr/rest/repos/repos?apiVersion=2022-11-28#create-a-repository-using-a-template
    #- { name: atournayre/default-template, include_all_branches: true }
  # Files
  files:
    - .github/dependabot.yml
    - .github/ISSUE_TEMPLATE/BUG-REPORT.yml
    - .github/ISSUE_TEMPLATE/config.yml
    - .github/ISSUE_TEMPLATE/FEATURE-REQUEST.yml
    - .github/PULL_REQUEST_TEMPLATE.md
    - docs/CONTRIBUTORS.md
    - docs/DEPLOY.md
    - docs/DEVELOP.md
    - docs/FAQ.md
    - docs/INSTALL.md
    - docs/ISSUES.md
    - docs/MAINTAINERS.md
    - docs/SECURITY.md
    - docs/TEST.md
    - docs/TODO.md
    - docs/UPGRADE.md
    - docs/USAGE.md
    - CHANGELOG.md
    - CODE_OF_CONDUCT.md
    - CONTRIBUTING.md
    - LICENSE
    - README.md
    - { path: composer.json, url: https://github.com/atournayre/symfony-skeleton/blob/master/composer.json }
    # Github actions
    - .github/workflows/labeler.yml
    - .github/labeler.yml
    - .github/reviewers.yml
  # Milestones
  milestones:
    - { title: 'Initial setup', description: "Initial setup of the environment", due_on: ~ }
    - { title: 'Model', description: 'Data model', due_on: ~ }
    - { title: 'Fixtures', description: 'Uses cases and test datas', due_on: ~ }
    - { title: 'Deployment scripts', description: 'Delivery/installation scripts', due_on: ~ }
    - { title: 'Staging environment', description: "Staging related configuration", due_on: ~ }
    - { title: 'Production environment', description: "Production related configuration", due_on: ~ }
    - { title: 'First version', description: 'First version for internal purposes or beta testers', due_on: ~ }
    - { title: 'Release 1.0', description: 'First release', due_on: ~ }
  # Codeowners
  codeowners:
    reviewers:
      defaults: defaut-reviewer
      repository_owners:
        - repository-owner
      infra_devs:
        - infra-devs-user
      architecture_devs:
        - architecture-devs-user
      frontend_devs:
        - frontend-devs-user
      backend_devs:
        - backend-devs-user
      full_team:
        - member-1
        - member-2
        - member-3
      lead_infra:
        - lead-infra-user
      lead_architecture:
        - lead-architecture-user
      lead_frontend:
        - lead-frontend-user
      lead_backend:
        - lead-backend-user
    patterns:
      - { pattern: 'Docker*', owners: [lead_infra] }
      - { pattern: 'docker*', owners: [lead_infra] }
      - { pattern: '.env*', owners: [lead_infra] }
      - { pattern: '.git*', owners: [lead_infra] }
      - { pattern: 'package*', owners: [lead_infra, lead_frontend] }
      - { pattern: '*.lock', owners: [lead_infra] }
      - { pattern: 'composer.*', owners: [lead_infra, lead_backend] }
      - { pattern: 'fixtures/', owners: [lead_architecture] }
      - { pattern: 'tests/', owners: [lead_architecture] }
      - { pattern: 'phpunit.xml.dist', owners: [lead_architecture] }
      - { pattern: 'tools/', owners: [lead_infra] }
      - { pattern: 'deploy/', owners: [lead_infra] }
      - { pattern: 'docs/', owners: [full_team] }
      - { pattern: '*.md', owners: [full_team] }
      - { pattern: '.github/workflows/', owners: [lead_infra] }
      - { pattern: 'migrations/*', owners: [lead_architecture] }
      - { pattern: '*.js', owners: [lead_frontend] }
      - { pattern: '*.css', owners: [lead_frontend] }
      - { pattern: 'assets/', owners: [lead_frontend] }
      - { pattern: 'templates/', owners: [lead_frontend] }
      - { pattern: '*.twig', owners: [lead_frontend] }
      - { pattern: '*.jpg', owners: [lead_frontend] }
      - { pattern: '*.png', owners: [lead_frontend] }
      - { pattern: '*.svg', owners: [lead_frontend] }
      - { pattern: '*.gif', owners: [lead_frontend] }
      - { pattern: '*.ico', owners: [lead_frontend] }
      - { pattern: '*.webp', owners: [lead_frontend] }
      - { pattern: 'webpack.config.js', owners: [lead_infra, lead_frontend] }
      - { pattern: '*.php', owners: [lead_backend] }

```


## Versionning
It is recommended for you to create a repository and version your different config files and templates, so you can recreate new config without overwriting your previous ones (or check changes thanks to GIT).

## Contributing

Contributions are always welcome!



## Authors

- [@atournayre](https://www.github.com/atournayre)

