<?php

namespace App;

class JiraRepository
{

    protected string $project;

    public function __construct($config)
    {
        $this->client = new AtlassianClient(
            $config['uri'],
            $config['username'],
            $config['api-token']
        );

        $this->setProject($config['default-project']);
    }

    public function searchIssues($jql, $fields = []): \Generator
    {
        $results = $this->client->getPaginated('api/3/search', [
            'jql' => $jql,
            'fields' => $fields
        ]);

        foreach ($results as $result) {
            foreach ($result->issues as $issue) {
                yield $issue;
            }
        }
    }

    public function setProject(string $projectName)
    {
        $this->project = $projectName;
    }

    public function getFields()
    {
        return $this->client->get('api/3/field');
    }

    public function findField($name)
    {
        $fields = $this->getFields();

        foreach ($fields as $field) {
            if ($field->name === $name || $field?->untranslatedName === $name) {
                return $field;
            }
        }

        return null;
    }

    public function getCurrentSprintIssues()
    {
        $issues = $this->searchIssues(
            "project = \"$this->project\" AND Sprint in openSprints()",
            'id,key,summary,worklog,assignee,customfield_10036'
        );

        return $issues;
    }


}
