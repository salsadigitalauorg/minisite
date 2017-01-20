# minisite

Minisite library for Drupal (https://www.drupal.org/project/minisite)

This library provides the ability to upload static 'minisites' to a Drupal website and maintain the minisite's look and feel.

## Introduction

A minisite is a small website with a narrow subject focus, also known as microsite or sitelet.

> A minisite is a website by which companies offer information about one specific product or product group. 
Typically, a minisite is enhanced by various multimedia content, such as an animated, narrated introduction, 
and accompanied by a visual scheme which complements the product well. - [Minisite - Wikipedia Features](https://en.wikipedia.org/wiki/Minisite)

## Features

- upload and extract minisite archives
- file blacklisting
- file whitelisting
- permission control

## Roadmap

- inject Google Analytics tracking code
- search api integration

## Installation

```composer require xing/minisite```

## Security

Strongly suggest that only allow trusted user upload minisite archive file. 
And use antivirus software to detect malicious software, including viruses. 
You may check [ClamAV](https://www.clamav.net/) which will verify that files uploaded to a site are not infected with a virus, and prevent infected files from being saved.