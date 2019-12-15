#! /bin/bash
if [[ $# -ne 1 ]]
then
	echo "Usage: $0 [List File]"
	exit 140
fi

listFile=$1
> "${listFile}_PromoCode" # Clear output Code file

issueCount=0;
while IFS=";" read -r name email
do
	echo "php issueCoupon.php \"$name\" \"$email\"" 
	output=$(php issueCoupon.php "$name" "$email")
	echo $output
	echo $output >> "${listFile}_PromoCode"
	
	((issueCount++))
done < "$listFile"

echo "Total issued: $issueCount"

