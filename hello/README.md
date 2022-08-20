# PHP Hello World AWS Lambda Container
This repository contains the second project in a series aimed at creating Serverless PHP Applications.

1. [Serverless PHP in AWS](https://github.com/DanielCraigie/serverless-php-in-aws) - explores the creation of PHP Lambda functions using Binaries in Lambda layers
2. This project covers a base implementation of a PHP Lambda function defined as a Docker container.
3. [Hello World PHP AWS SAM Example]() - contains an example implementation of PHP Lambda Functions structured behind the [AWS API Gateway](https://aws.amazon.com/api-gateway/) service

AWS announced [Lambda support for container images in 2020](https://aws.amazon.com/blogs/aws/new-for-aws-lambda-container-image-support) providing an alternative to the original method of [deploying code as a Zip file](https://docs.aws.amazon.com/lambda/latest/dg/configuration-function-zip.html).
The main disadvantage of deploying PHP scripts in a Zip file was that you had to define a Custom PHP Runtime [in a Layer](https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html) so the Lambda service could execute the code.

Defining functions in container images provides two main benefits to the developer:
1. the code and the (optional) custom runtime are defined in a single docker image
2. Developers run Lambda container images locally without having to deploy to AWS for testing

## File Structure
### Lambda
- bootstrap - Lambda entry point, interacts with the [Runtime API](https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html)
- Dockerfile - Docker image builder
### PHP
- php/src/LambdaFunction.php - main execution method (put your code here)
- php/index.php - PHP root execution script
- php/composer.json - Composer configuration file
- php/composer.lock - Composer lock file
- php/php.ini - Optional PHP runtime configuration

## Development
The container has been pre-configured with files required to process function calls coming from the Lambda Service (see above).

When the function is invoked (via the Lambda Runtime API) the `bootstrap` file will execute the `index.php` file via a PHP CLI call.
The `index.php` file will call `LambdaFunction::run()` and pass in an array of parameters received by the Lambda invocation call.

The `run()` function is where the PHP Lambda functions code goes, Any (text/HTML) returned from this function will form the body of the Lambda functions response.

## Testing
### Local
Execute the following commands to build and run the container:\
`docker build -t hello-world-php-aws-lambda-container .`\
`docker run -p 9000:8080 hello-world-php-aws-lambda-container`

The container will wait to process calls made via HTTP requests (as it would in AWS).

Make HTTP calls to test your code:\
`curl "http://localhost:9000/2015-03-31/functions/function/invocations" -d '{"name": "Daniel"}'`

Any output from the function will be returned in the HTTP response.

### Remote
You can also test your code by deploying the image to a Lambda function in AWS using the Deployment instructions below.

## Deployment

This section assumes you already have an [AWS account](https://aws.amazon.com) and (optionally) installed the [AWS Command Line Interface](https://aws.amazon.com/cli/).  You can setup an AWS account free of charge, without having to make any financial commitments.  AWS charges you for what you use instead of the ability to use.

Each stage has UI & CLI examples, you should perform one **or** the other.

All examples default to the Europe/London (eu-west-2) region but you should change this to your [closest geographic region](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/using-regions-availability-zones.html#concepts-regions) (except N.Virginia, you should **never** use us-east-1).

### Upload image to AWS Elastic Container Registry (ECR)
#### AWS Console
1. Navigate to the [ECR Repositories](https://eu-west-2.console.aws.amazon.com/ecr/repositories) page
2. Click the "Create repository" button 
   1. Enter "hello-world-php-aws-lambda-container" into the Repository Name field
   2. Click the "Create repository" button
3. Back on the ECR Repositories page, select the new repository and click the "View push commands" button
   1. A dialog will appear with instructions & commands required to upload the image

#### AWS CLI
1. `region="eu-west-2"`
2. `repositoryName="hello-world-php-aws-lambda-container"`
3. `aws --region $region ecr create-repository --repository-name $repositoryName`
4. `repositoryUri=$(aws --region $region ecr describe-repositories --repository-name $repository --query 'repositories[].repositoryUri' --output text)`
5. `aws ecr get-login-password --region $region | docker login --username AWS --password-stdin $(echo $repositoryUri | cut -d "/" -f 1)`
6. `docker build -t $repositoryName .`
7. `docker tag ${repositoryName}:latest ${repositoryUri}:latest`
8. `docker push ${repositoryUri}:latest`

### Create Lambda Function
#### AWS Console
1. Open the [Lambda](https://eu-west-2.console.aws.amazon.com/lambda/home) service and select the "Functions" menu item
2. Click the "Create function" button
   1. Select the "Container Image" radio button at the top of the page
   2. Provide a name for the function
   3. Use the "Browse images" button to select the ECR image to use
   4. Ensure the "Architecture" option matches the architecture used to build the image
      1. The function will fail to run if the incorrect architecture is selected, this can be edited after the function has been created
   5. Click the "Create function" button

#### AWS CLI
1. `region="eu-west-2"`
2. `roleName="LambdaExecutionRole"`
3. `functionName="hello-world-php-aws-lambda-function"`
4. `repositoryName="hello-world-php-aws-lambda-container"`
5. `architectures="arm64"`
6. `aws iam create-role --role-name $roleName --assume-role-policy-document '{"Version":"2012-10-17","Statement":[{"Effect":"Allow","Principal":{"Service":"lambda.amazonaws.com"},"Action":"sts:AssumeRole"}]}'`
7. `aws iam attach-role-policy --role-name $roleName --policy-arn $(aws iam list-policies --scope AWS --query "Policies[?PolicyName=='AWSLambdaExecute'].Arn" --output text)`
8. `aws lambda create-function --function-name $functionName --role $(aws iam get-role --role-name LambdaExecutionRole --query 'Role.Arn' --output text) --architectures $architectures --package-type Image --code ImageUri=$(aws --region $region ecr describe-repositories --repository-names $repositoryName --query 'repositories[].repositoryUri' --output text):latest`

### Execution
#### AWS Console
1. Open the [Lambda](https://eu-west-2.console.aws.amazon.com/lambda/home) service and select the "Functions" menu item
2. Select the function from the list
3. Click on the "Test" tab
4. The UI should default to creating a new test, provide a name for the test
5. Modify the "Event JSON" to pass in the test data you want to send to the function
6. Click the "Test" button to execute
7. You should see an "Execution result" panel appear containing execution details and result if nothing went wrong.

#### AWS CLI
1. `region="eu-west-2"`
2. `functionName="hello-world-php-aws-lambda-function"`
3. `aws --region $region lambda invoke --function-name $functionName --cli-binary-format raw-in-base64-out --payload '{"name":"Daniel"}' response.json`
4. You will see execution details printed in the CLI, the function response will be saved to `response.json`

## Resources
* [Lambda Dev Guide: Creating Lambda container images](https://docs.aws.amazon.com/lambda/latest/dg/images-create.html)
* [AWS GitHub: PHP Lambda container image demo](https://github.com/aws-samples/php-examples-for-aws-lambda/tree/master/0.7-PHP-Lambda-functions-with-Docker-container-images)
* [AWS Blog: Building PHP Lambda functions with Docker container images](https://aws.amazon.com/blogs/compute/building-php-lambda-functions-with-docker-container-images/)
* [A Cloud Guru: Packaging AWS Lambda functions as container images](https://acloudguru.com/blog/engineering/packaging-aws-lambda-functions-as-container-images)
* [AWS Dev Guide: Troubleshoot container image issues in Lambda](https://docs.aws.amazon.com/lambda/latest/dg/troubleshooting-images.html)
