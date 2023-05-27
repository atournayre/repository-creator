# :bug: It's not a feature, it's a bug!

Delete the section if the PR does not fix a problem, otherwise add the link to the ticket or the issue.

# :loudspeaker: Description

Please include a summary of the changes and the related issue. Please also include relevant motivation and context. List all dependencies required for this change.

# :clipboard: Type of change

Please remove options that are not relevant.

| | Retail
| --- | ---
| :heavy_check_mark: | Bugfix (unbroken change that solves a problem)
| :heavy_check_mark: | New Feature (continuing change that adds functionality)
| :heavy_check_mark: | Breaking change (fix or functionality that would prevent existing functionality from working as intended)
| :heavy_check_mark: | Non-breaking change (fix or functionality that would not prevent existing functionality from working as intended)
| :heavy_check_mark: | Improvement
| :heavy_check_mark: | Refactoring
| :heavy_check_mark: | This modification requires an update of the documentation
| :heavy_check_mark: | This change requires communication with the team

# :pencil: How was this tested?

Please describe the tests you ran to verify your changes. Provide instructions so we can reproduce. Please also list all relevant details for your test setup

:heavy_check_mark: Test A
:heavy_check_mark: Test B

# :white_check_mark: Checklist

| | Retail
| --- | ---
| :heavy_check_mark: | My code follows the conventions of this project
| :heavy_check_mark: | I did a self-review of my code
| :heavy_check_mark: | I commented my code, especially in hard-to-understand areas
| :heavy_check_mark: | I have made the corresponding changes to the documentation
| :heavy_check_mark: | My changes do not generate any new warnings
| :heavy_check_mark: | I have added tests that prove my fix is effective or my feature is working
| :heavy_check_mark: | New and existing unit tests pass locally with my changes
| :heavy_check_mark: | All dependent changes have been merged and released to downstream modules
| :heavy_check_mark: | Local QA passes and no new anomalies have been introduced
| :heavy_check_mark: | lint:container pass
| :heavy_check_mark: | lint:yaml config pass

# :question: How to apply the change?

| | Stock | Order
| --- | --- | ---
| :heavy_check_mark: | Build the environment | `make build`
| :heavy_check_mark: | Configure Environment | `compose dump-env dev`
| :heavy_check_mark: | Delete tables and sequences |
| :heavy_check_mark: | Restart Docker | `make restart`
| :heavy_check_mark: | Reload Fixtures | `make fixtures`

# :information_source: Additional information
