# Symfony HTTP Client PSR adapter

This package wraps the relevant PSR interfaces to provide Symfony's `HttpClientInterface`.

- It is not complete with respect to the supported options
- It will throw exceptions when it encounters unsupported options as required by the contract
- It DOES NOT use lazy responses and which explicitly VIOLATES the contract.

## Why is this needed

Currently packages like `symfony/mailer` require an implementation of Symfony's `HttpClientInterface`. With the PSR 
standards related to sending HTTP requests maturing projects not using these components in a standalone fashion might be
forced into using Symfony's HTTP Client when they already have another HTTP Client implementation set up.

This package provides a lightweight adapter that allows you to use your existing PSR18 Http Client and pass it to 
Symfony components that require Symfony's `HttpClientInterface`.
Since we do not support lazy responses you should evaluate what kind of usage you expect before using this adapter. 
