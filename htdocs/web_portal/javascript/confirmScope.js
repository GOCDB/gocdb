function confirmScopeSelect(scopeName, parentName, childName, boxchecked)
{
    if(boxchecked){
        var agree=confirm("The scope \"" + scopeName 
                          + "\" is not applied to the parent " + parentName 
                          + ". Apply it to " + childName + "?");
        if (agree)
            return true ;
        else
            return false ;
    }
    return true;
}
