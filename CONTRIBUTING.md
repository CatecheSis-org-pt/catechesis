# Contributing guidelines

## Introduction

Welcome to the CatecheSis community and thank you for considering collaborating with us!

Our team is small, and we are still taking our first steps into turning CatecheSis into an open-source project and community.
At this stage, many processes and practices are not yet clearly defined nor documented, so we start by apologizing for that.

If you are willing to help, the easiest way to get in touch with us is through our community on Discord or through the [contact form](https://catechesis.org.pt/contact) on catechesis.org.pt.
Tell us how you would like to contribute and we will be happy to on-board you! :)


## How you can help

There are multiple ways in which you can contribute to CatecheSis.
We name just a few:

- Spreading the word about CatecheSis in your parish or in other neighboring parishes;

- Helping users and answering their questions, either in person, or in our online community on Discord;

- Writing tutorials or even producing video tutorials;

- Writing documentation (user manual);

- Identifying and reporting errors;

- Making suggestions;

- Helping us or the parishes that use CatecheSis with legal advice on data protection issues (GDPR);

- Implementing code for new functionalities and corrections;

- Translating CatecheSis to another language (*future work*);

You may even contribute with your uniqueness in ways that we have not imagined!
Do you have a particular skill or an idea that could improve CatecheSis or the way we serve the parishes that use it? Tell us!


We are **not** looking for monetary contributions.


## Ground rules

So you decided to give some of your time and talents to the CatecheSis community. Great news!

First, we need to establish some rules so that everybody is on the same page:

- We like to **respect everybody** and expect you to do the same. There is a [Code of Conduct](CODE_OF_CONDUCT.md) that you must read and respect.


### How to report a bug

- Please **do not open issues on Github to report errors or ask questions**. Open a support ticket in our Discord community instead, and a community member will be happy to listen to you and help you.


### How to suggest a feature or enhancement

- Please **do not open issues on Github for suggested features** either. At this stage, we prefer to receive all the input from one place, and that is our Discord server. Use the *suggestions* channel on Discord for that.

- If you have an idea for a feature that you would like to work on, talk to a staff member on the Discord server first.


### Rules for developers

- If you want to help by implementing code but do not know what to do or where help is most needed, ask to a staff member on the Discord server and we will happily find a task that suits your likings and that is aligned with the project's current needs.

- If you already have an idea that you would like to implement, do this:

  - Explain your ideia to the staff members on Discord. In case your idea responds only to particular need of your parish, they may help you refine your idea by making small adjustments to make it applicable to other parishes and thus more valuable to be included in the CatecheSis code base.
  
  - If approved, implement your feature according to what was agreed with the staff. Implement it in a git branch whose name starts with the issue number that was assigned to your idea, as instructed by the staff members.

- Always write internal documentation (PHP Doc), in English, in the code that you implement.

- Use the English language for the names of variables, functions, classes and any other computational objects.

- When you are ready to submit your code, open a pull request in Github.

- If the staff members ask you to make some changes to your pull request (such as to comply with the documentation needs and code conventions) please respond politely.


### Releasing versions

- The CatecheSis staff decides which features will be included in the next release.

- Only the CatecheSis staff releases official CatecheSis versions, which are published on Github and [catechesis.org.pt](https://catechesis.org.pt).

- The AGPL-3.0 license allows you to fork the project, implement and release your own custom versions (e.g. for your parish), provided that you also publish the source code. However, we would prefer that you contribute to the official CatecheSis project so that all the community may benefit from your valuable contributions! We believe we can go further together than divided.


## Setting up the development environment

If you have programming skills and choose to help us in that way, here are the instructions needed to setup your development environment.

- We have set up the project in Docker, so that it is easier for you and other new contributers to get started.
So, start by [installing Docker](https://www.docker.com/get-started/) in your development machine. 

- Most of the code base is written in PHP, HTML, CSS and Javascript. You are free to choose whatever IDE or text editor you like to develop.

- Follow the steps in [doc/Docker.md](doc/Docker.md). You will have CatecheSis up and running in your machine in no time.

- As you develop and change source files in your computer, changes are immediately reflected inside the docker containers (due to bind volumes), so you can see them immediately take effect on the browser.

- Follow the steps in [doc/Compiling_user_manuals.md](doc/Compiling_user_manuals.md) to configure the git submodule that makes the user manuals also available in this repo.

----

For any question not addressed in this document, feel free to ask us in our Discord server. :)

Thank you for your time and welcome!
