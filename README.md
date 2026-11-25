# Harvest ☞ Kimai Migrator

As you probably know, Harvest has been bought by Bending Spoons (🤮) and is currently being enshittified and ensloppified along with massive, and frankly [ridiculous price hikes](https://www.bbc.co.uk/news/articles/clyq011414eo).

[Kimai](https://www.kimai.org) is a great alternative to Harvest, and this project exists to perform one-off migrations to it in order to preserve the most important data (to me) in your Harvest account.

## What this does:

- Seeds a mapping file so you can manually map Harvest user ids to Kimai user ids
- Copies all Harvest tasks to Kimai activities
- Copies all Harvest clients to Kimai customers
- Copies all Harvest projects to Kimai projects
- Copies all Harvest time entries to Kimai time sheets
- Hides Kimai projects that were previously archived on Harvest
- Hides Kimai customers that were previously archived on Harvest

During these procedures, state is reliably stored to JSON mapping files in the [state](./state) directory, and your time entries should match exactly what you see on Harvest.

Invoiced time entries are marked as "Exported" in Kimai so all of your un-invoiced time should match.

Whilst time durations and date _(day)_ is correct, the time-of-day will likely be around midday for every entry. If you care, you may want to look into fixing that.

## What it doesn't do

- Anything to do with users. It seeds a state file for user mapping but beyond that, you have to create your own users in Kimai and then map those in the users state file after you've had a failed migration.
- Anything to do with "Teams" - Don't care, I'm a billy no-mates
- Anything to do with invoices - You can't create invoices via the Kimai API, so this just isn't possible.
- Customer and Project quotas/budgets - You'll have to re-create these once the basic projects and customers are set up
- Any custom customer, project, task or user hourly rates. The time entries have the rates embedded when they come over wire - I just don't have enough complexity in my setup to do the work to map the Harvest config for this to Kimai.
- There is no date-based bounding of the migration - it's all or nothing.

## Preparation

Your Kimai installation should be mostly empty except for users. This thing doesn't _read_ from Kimai and look for matching project/customer names etc. It just blindly creates them and stores identifiers in state once it's done, so emptiness is probably a good idea.

I used this on a self-hosted (2.73.0) copy of Kimai, but I don't see why it wouldn't work on [the cloud version, which you absolutely should consider using](https://www.kimai.org/en/).

## Install

1. Clone this repository locally
2. Copy the shipped env file with `cp example.env .env`
3. Edit `.env` according to your needs
4. Run `make install` to build the PHP image and install PHP dependencies
5. Run `make migrate` to run the migration

You _should_ see an exception back trace when you get to the bit where it starts copying over the time entries.
At this point, you should open up the `users.json` file in the state directory and map Kimai users to Harvest users.

Run `make migrate` again and you should be in business.

## Contributions

I'm unlikely to spend any more time on this, but will try to merge in changes that will benefit others if someone provides them.

There are no tests 😱 but `mago` is used for everything else. Make sure you run `make qa`.
