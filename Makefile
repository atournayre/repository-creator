# Build the phar
build-phar:
	rm config/github_token
	phar-composer build . .
