update purchase pu, (select t1.id_purchase, pa.id_payment
    from (
    select p.id_purchase, f.id_farm, p.fk_user as id_user, p.fk_contract as id_contract
    from purchase p
    left join product_distribution pd on pd.id_product_distribution = p.fk_product_distribution
    left join product pr on pd.fk_product = pr.id_product
    left join farm f on f.id_farm = pr.fk_farm
    where p.fk_payment is null and p.fk_contract is not null) t1
    left join payment pa on pa.fk_user = t1.id_user and pa.fk_contract = t1.id_contract and pa.fk_farm = t1.id_farm
    where pa.id_payment is not null) as t2
set pu.fk_payment = t2.id_payment
where pu.id_purchase = t2.id_purchase;