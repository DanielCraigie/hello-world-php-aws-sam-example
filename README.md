# Hello World PHP AWS SAM Example

This repository is the third in a series of projects focused on creating Serverless PHP Applications in AWS.

1. [Serverless PHP in AWS](https://github.com/DanielCraigie/serverless-php-in-aws) - explores the creation of PHP Lambda functions using Binaries in Lambda layers
2. [PHP HelloWorld AWS Lambda Container](https://github.com/DanielCraigie/hello-world-php-aws-lambda-container) - leverages functionality in the Lambda service to define Functions from Docker Images
3. This project contains an example implementation of PHP Lambda Functions using the [AWS Serverless Application Model](https://aws.amazon.com/serverless/sam/)

In this project we make a couple of PHP Lambda Functions and structure them behind an [AWS API Gateway](https://aws.amazon.com/api-gateway/) implementation.  This will allow us to define a very basic Serverless Web Backend/API service from PHP scripts.

PHP Functions will be defined as a Docker Images and made available as methods in a RESTful API. 

The AWS Serverless Application Model will be used to do all the heavy lifting in AWS.  SAM takes much of the complexity out of building, testing, deploying & managing a Serverless Application in AWS.  To put it simply, you give it a ([CloudFormation](https://aws.amazon.com/cloudformation/) like) specification along with the Application code and it does all the hard work for you.

## Project Structure

- `samconfig.toml` is the SAM configuration file used when deploying the Application (can be modified by the deploy process)
- `template.yaml` is the SAM definition file that defines the project infrastructure
- `hello` contains resources to build a Docker Image of a PHP Lambda Function that says "Hello"
- `goodbye` contains resources to build a Docker Image of a PHP Lambda Function that says "Goodbye"

### Serverless Application Model

The `template.yaml` file is used by SAM to construct all required resources in AWS.  It should be relatively easy to identify that it currently defines two Functions (Hello/Goodbye) in the "Resources" section.  Each Function has a defined Type: [AWS::Serverless::Function](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/sam-resource-function.html) with associated Properties. 

#### Events
What may not be immediately obvious is that we have also included everything required for SAM to automatically create an API Gateway resource and link each Lambda Function as a named `method`.

This is achieved in the [Events](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/sam-resource-function.html#sam-function-events) Property in each of the functions.

Like the Functions, each event has a Type that resolves to an [EventSource](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/sam-property-function-eventsource.html) definition that SAM uses to construct the correct resources in AWS.

#### Architectures
It's important to note the use of the [Architectures](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/sam-resource-function.html#sam-function-architectures) property in each of the functions.  Lambda supports two instruction set architectures:
 
- `x86_64` (default)
- `arm64`

You will need to ensure that the "Architectures" property reflects the architecture that the Docker Images are built in.  I'm currently running on Apple (ARM) silicon and couldn't get the Lambda functions to execute until I changed this settting from the x86 default.

#### Metadata
Some may be wondering why the `ImageUri` value hasn't been set after the `PackageType` has been set to "Image".

As we are defining our own [custom Runtime](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/building-custom-runtimes.html) we need to instruct SAM how to Build the Images we are pushing up for each Lambda Function.  Once SAM has built the Images locally it will automatically set this value.

If you manually push a Docker Image to [ECR](https://aws.amazon.com/ecr/) and provide the URL via `ImageUri` SAM won't be able to run your function locally:

`The resource AWS::Serverless::Function 'HelloFunction' has specified ECR registry image for ImageUri. It will not be built and SAM CLI does not support invoking it locally.`

### PHP Lambda Functions

This project currently defines two Lambda Functions (hello/goodbye) as Docker Images using the framework articulated in the previous [PHP HelloWorld AWS Lambda Container](https://github.com/DanielCraigie/hello-world-php-aws-lambda-container) repository.

The `php/src/LambdaFunction.php` Class contains a `run()` method which is the start point of the custom PHP functionality.

#### Adding PHP Functions
You can add more functions to the project by either duplicating an existing folder or starting fresh by cloning the Lambda Container repository to a new directory:

1. Ensure you are in the project root
2. `git clone git@github.com:DanielCraigie/hello-world-php-aws-lambda-container.git {function-name}`
3. `rm -rf {function-name}/.git*`
4. modify the `template.yaml` file to add the new function
5. add PHP code to the new function

#### Going Interactive
The default functions will automatically respond with (Hello/Goodbye) text when invoked.  However, they are structured to receive a JSON payload and pass it into the `LambdaFunction::run()` method as an Array.

This means the code can be modified to process user input before responding/completing execution.

#### PHP Modifications

- use `composer.json` to add/remove packages, dependencies are updated by the Dockerfile each time an Image is built
- use `php.ini` to modify the PHP default runtime
- the PHP Version is set at the top of each Dockerfile

#### Error Handling
The `php/index.php` file is setup to capture PHP Runtime/Fatal errors and report them back to the Lambda service via it's API.  You will be able to view the Error details in the logging associated with the Lambda Function.

## Testing

One of the main advantages of using AWS SAM is that you can use it to construct a mock of your Serverless Application locally allowing you to test before deploying to AWS.
SAM has it's own [CLI application](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/serverless-sam-cli-install.html) (separate to the existing AWS CLI) that you will need to install before continuing.

Follow these steps to build a local testing environment:

1. Make sure you are in the project root
2. Run `sam build` to construct the project
   1. this will build all defined Docker Images
   2. creates an `.aws-sam` artifacts directory for testing/deployment
3. `sam validate` will verify build
4. Invoke functions locally
   1. `sam local invoke HelloFunction`
   2. `sam local invoke GoodbyeFunction`

As the basic functions aren't iteractive you will find the invocation will start and immediatly stop because each function will automatically respond with it's relative payload.

## Deployment

1. Run `sam deploy --guided` and follow the instructions
   1. there will be verbose output as the process completes
2. Login to the AWS Console and navigate to the API Gateway service
   1. You should see your API on the dashboard, click the name to open it
   2. Click on the root `GET` method in the Resources list
   3. Click the "Test" link to open the test page
   4. Click the blue "Test" button to trigger an API call
   5. You should see a successful request breakdown on the right hand side of the page
   6. Repeat for the "Goodbye" GET method
3. Run `sam delete` to remove the deployed components from AWS
   1. this won't remove the S3 bucket created to support the deployment

## To-Do
- [ ] Find a way for functions to share the base Image
  - each image has same base size before code/dependencies
- [ ] Get API Gateway endpoint to test calls from outside AWS
- [ ] Correct permissions for functions on deployment
  - HelloFunction may not have authorization defined.
  - GoodbyeFunction may not have authorization defined.

## Resources

- [AWS SAM Developer Guide](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/what-is-sam.html)
  - [Building custom runtimes](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/building-custom-runtimes.html)
- [AWS API Gateway Developer Guide](https://docs.aws.amazon.com/apigateway/latest/developerguide/welcome.html)
- [Using container image support for AWS Lambda with AWS SAM](https://aws.amazon.com/blogs/compute/using-container-image-support-for-aws-lambda-with-aws-sam/)
- [AWS Lambda Developer Guide](https://docs.aws.amazon.com/lambda/latest/dg/welcome.html)