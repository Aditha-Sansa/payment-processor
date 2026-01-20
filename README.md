## Payment Processing System

### Application setup:

#### Important
The docker setup in this project is based on two environment profiles. Which are local and production. We are using horizon and db-dev only for dev environment to help us with testing and debugging. The "prod" profile will be connecting to a RDS database, SQS to handle the queues etc.

#### Follow these steps to setup the project in your development environment.

- Run `git clone https://github.com/Aditha-Sansa/payment-processor.git .` inside your project folder.
- Copy the .env.example file to .env.local file and update the following values
1. `PAYMENTS_UPLOAD_DISK=local`
2. `PAYMENTS_WORK_DISK=local`
3. `PAYMENTS_CHUNK_ROWS=5000`
4. `EXCHANGE_PROVIDER=exchangerateapi`
5. `QUEUE_CONNECTION=redis`
6. `CACHE_STORE=redis`
7. `EXCHANGERATE_API_COM_KEY=add you api key from https://app.exchangerate-api.com`
8. `PAYMENTS_QUEUE_CHUNKING=imports`
9. `PAYMENTS_QUEUE_PROCESSING=processing`
    
- Run `ENV_FILE=.env.local docker compose --profile local up -d --build` and confirm if the containers are up and running by checking `docker compose ps`
- Check if app-dev, db-dev, horizon, redis, scheduler-dev services are running.
- In the localhost services like app-dev, schedular-dev is using a bind mount in host machine to access the application code. 
- So inside the project root run these commands:
`sudo chown -R $USER:$USER .`
`sudo chmod -R g+rwX storage bootstrap/cache`

Run the following artisan command in your app-dev service

- `docker compose exec app-dev composer install`
- `docker compose exec app-dev php artisan migrate`

#### Production server setup

For the deployment, following values should be updated in the .env.production file:
- `FILESYSTEM_DISK=s3`
- `QUEUE_CONNECTION=sqs`
- `QUEUE_NAME=payments-processor`
- `PAYMENTS_QUEUE_CHUNKING=payments-import`
- `PAYMENTS_QUEUE_PROCESSING=payments-processor`
- `QUEUE_FAILED_DRIVER=database-uuids`
- `SQS_PREFIX=https://sqs.us-east-1.amazonaws.com/<aws-account-id>`
- `CACHE_STORE=redis`
- `PAYMENTS_UPLOAD_DISK=s3`
- `PAYMENTS_WORK_DISK=s3`
- `PAYMENTS_CHUNK_ROWS=5000`
- `EXCHANGE_PROVIDER=exchangerateapi`
- `QUEUE_CONNECTION=sqs`

For the file storage we are using s3 drive in production and queues are setup in SQS. 

#### Configurations
To manage the workload in the queues, we are using 2 queues in this application one for the chunking purpose and another queue for processing the chunked records. (refer to the `payments.php` config file)

Depending on the available memory you can allocate, update the `PAYMENTS_CHUNK_ROWS` values so that the program can decide how many rows to process per chunk. A default value of 10,000 is set for this value.

Exchange rate providers are managed by `PaymentProcessingServiceProvider` provider. I have registered `FrankfurterProvider` first but later realized some of the currency information are not available, then added `ExchangerateAPIProvider` later.

`EXCHANGE_CACHE_TTL` decides how long the fetched exchange rates from api should be kept in the cache.

There is a scheduled command added to run everyday at 1 A.M UTC to fetch the currency values from api. Initially when you first launch the application run `docker compose exec app-prod php artisan exchange-rates:fetch` OR `docker compose exec app-dev php artisan exchange-rates:fetch` to manually fetch the latest values to continue testing.

#### API Endpoints

This project has 2 endpoints currenty. One for importing the records and another one to get the status of the records processing in system.

Please check the below link to download the insomnia/postman collection file and a stripped down version of the `sample_transactions.csv` with few records to test the endpoint first.

When you are testing with insomnia or postman just make sure to add big value to request timeout value in the client or just completey remove it.

Resources link: https://drive.google.com/drive/folders/1a0KryGnVvZx6jEXMJsarDWGxoktawOVz?usp=drive_link