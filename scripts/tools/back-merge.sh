#!/bin/sh

if [ -z "$BACKMERGE_TOKEN" ]; then
  echo "BACKMERGE_TOKEN not set"
  echo "Please set the GitLab Private Token as BACKMERGE_TOKEN"
  exit 1
fi

create_mr(){
  # Extract the host where the server is running, and add the URL to the APIs.
  DOMAIN=$(echo ${CI_PROJECT_URL} | awk -F/ '{print $3}')
  HOST="https://${DOMAIN}/api/v4/projects/"

  echo "Creating MR to $1 branch"

  # The description of our new MR, we want to remove the branch after the MR has been closed.
  BODY="{
    \"id\": ${CI_PROJECT_ID},
    \"source_branch\": \"master\",
    \"target_branch\": \"$1\",
    \"remove_source_branch\": false,
    \"title\": \"${CI_COMMIT_REF_NAME}\",
    \"assignee_id\":\"${GITLAB_USER_ID}\"
  }";

  # Require a list of all the merge request and take a look if there is already
  # one with the same source branch.
  LISTMR=`curl -X GET "${HOST}/${CI_PROJECT_ID}/merge_requests?state=opened" \
  --header "Private-Token: ${BACKMERGE_TOKEN}" \
  --header "Content-Type: application/json"`;
  COUNTBRANCHES=`echo ${LISTMR} | grep -c "\"target_branch\":\"$1\",\"source_branch\":\"master\""`;

  # No MR found, let's create a new one.
  if [ ${COUNTBRANCHES} -eq "0" ]; then
    curl -X POST "${HOST}/${CI_PROJECT_ID}/merge_requests" \
        --header "Private-Token: ${BACKMERGE_TOKEN}" \
        --header "Content-Type: application/json" \
        --data "${BODY}";
    echo "Opened a new merge request: ${CI_COMMIT_REF_NAME} and assigned to you";
    #exit;
    else
    echo "No new merge request opened";
  fi

}

accept_mr(){
  echo "Accept of MRs"

  # Extract the host where the server is running, and add the URL to the APIs.
  DOMAIN=$(echo ${CI_PROJECT_URL} | awk -F/ '{print $3}')
  HOST="https://${DOMAIN}/api/v4/projects/"

  CI_MERGE_REQUEST_ID=$(curl -X GET "${HOST}/${CI_PROJECT_ID}/merge_requests?private_token=${BACKMERGE_TOKEN}&state=opened&source_branch=master" | jq -r ".[]|select(.sha == \"$CI_COMMIT_SHA\")|.iid")
  echo "CI_MERGE_REQUEST_ID" ${CI_MERGE_REQUEST_ID}

  MR_IDS=$(echo $CI_MERGE_REQUEST_ID | tr " " "\n")
  for MR_ID in $MR_IDS
  do
   echo $MR_ID
   curl -X PUT "${HOST}/${CI_PROJECT_ID}/merge_requests/$MR_ID/merge" \
   --header "Private-Token: ${BACKMERGE_TOKEN}" \
   --header "Content-Type: application/json"
  done
}

create_mr $1
accept_mr